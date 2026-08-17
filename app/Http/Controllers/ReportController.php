<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Concerns\RendersPrintView;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Project;
use App\Models\Sale;
use App\Models\StatusChangeLog;
use App\Services\ShareholderProfitService;
use App\Support\MisPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ExportsSpreadsheet;
    use RendersPrintView;

    public function index(Request $request): InertiaResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return Inertia::render('Reports/Index', $this->payload($request));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $data = $this->payload($request);
        $restricted = $request->user()?->isRestrictedShareholder() ?? false;

        $rows = [
            ['Summary', '', '', '', ''],
            ['Sales', $data['summary']['sales_total'], '', '', ''],
            ['Other income', $data['summary']['income_total'], '', '', ''],
            ['Revenue', $data['summary']['revenue_total'], '', '', ''],
            ['Operating expenses', $data['summary']['operating_expense_total'], '', '', ''],
            ['Salaries', $data['summary']['salary_total'], '', '', ''],
            ['Net profit', $data['summary']['net_profit'], '', '', ''],
            ['', '', '', '', ''],
            ['Shareholder', 'Project', 'Share %', 'Entitled', 'Paid'],
        ];

        foreach ($data['partners'] as $partner) {
            $rows[] = [
                $partner['name'],
                $partner['project_name'] ?? '',
                $partner['ownership_percentage'],
                $partner['entitled_share'],
                $partner['paid_total'],
            ];
        }

        if (! $restricted) {
            $rows[] = ['', '', '', '', ''];
            $rows[] = ['Type', 'Date', 'Reference', 'Party', 'Amount'];

            foreach ($data['sales'] as $sale) {
                $rows[] = [
                    'Sale',
                    $sale->contract_date?->toDateString(),
                    $sale->sale_no,
                    $sale->customer?->name,
                    $sale->total_price,
                ];
            }

            foreach ($data['expenses'] as $expense) {
                $rows[] = [
                    'Expense',
                    $expense->expense_date?->toDateString(),
                    $expense->expense_no,
                    $expense->description,
                    $expense->amount,
                ];
            }
        }

        return $this->streamExport(
            'mis-report-'.$data['filters']['from'].'-to-'.$data['filters']['to'],
            ['Col A', 'Col B', 'Col C', 'Col D', 'Col E'],
            $rows,
            $request->string('format')->toString() ?: 'xlsx',
        );
    }

    public function print(Request $request): Response
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return $this->printView('print.reports', $this->payload($request));
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        $user = $request->user();
        $restricted = $user?->isRestrictedShareholder() ?? false;
        [$from, $to, $preset] = MisPeriod::resolve(
            $request->string('preset')->toString() ?: null,
            $request->input('from'),
            $request->input('to'),
        );

        $projectId = $request->integer('project_id') ?: null;

        if ($restricted) {
            $allowed = $user->assignedProjectIds();

            if ($projectId && ! in_array($projectId, $allowed, true)) {
                abort(403);
            }

            if (! $projectId && count($allowed) === 1) {
                $projectId = $allowed[0];
            }
        }

        $breakdown = ShareholderProfitService::breakdown($from, $to, $projectId);
        $partners = ShareholderProfitService::allPartners($from, $to, $projectId);

        if ($restricted && $user->partner) {
            $partners = $partners->where('id', $user->partner->id)->values();
        }

        $expenses = Expense::query()
            ->with('category:id,name,code')
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($restricted, fn ($q) => $q->whereIn('project_id', $user->assignedProjectIds() ?: [0]))
            ->latest('expense_date')
            ->limit(100)
            ->get();

        $sales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,currency', 'project:id,name'])
            ->where('sales.status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $from->toDateString())
            ->whereDate('contract_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($restricted, fn ($q) => $q->whereIn('project_id', $user->assignedProjectIds() ?: [0]))
            ->latest('contract_date')
            ->limit(100)
            ->get();

        $salaryIds = ExpenseCategory::salaryIds();
        $salaries = $expenses->whereIn('expense_category_id', $salaryIds)->values();

        $byCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.status', 'approved')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->selectRaw('expense_categories.name as category, COUNT(*) as count, SUM(expenses.amount) as amount, SUM(expenses.amount_base) as amount_base')
            ->groupBy('expense_categories.name')
            ->orderByDesc('amount_base')
            ->get();

        $expensesByCurrency = Expense::query()
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($restricted, fn ($q) => $q->whereIn('project_id', $user->assignedProjectIds() ?: [0]))
            ->selectRaw('currency, COALESCE(SUM(amount), 0) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $incomesByCurrency = Income::query()
            ->where('status', 'approved')
            ->whereDate('income_date', '>=', $from->toDateString())
            ->whereDate('income_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($restricted, fn ($q) => $q->whereIn('project_id', $user->assignedProjectIds() ?: [0]))
            ->selectRaw('currency, COALESCE(SUM(amount), 0) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $activity = StatusChangeLog::query()
            ->with(['subject', 'changedBy:id,name'])
            ->where('subject_type', Project::class)
            ->whereBetween('created_at', [$from, $to])
            ->when($projectId, fn ($q) => $q->where('subject_id', $projectId))
            ->latest()
            ->limit(30)
            ->get();

        $projects = Project::query()
            ->orderBy('name')
            ->when($restricted, fn ($q) => $q->whereIn('id', $user->assignedProjectIds() ?: [0]))
            ->get(['id', 'name']);

        return [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'project_id' => $projectId,
                'preset' => $preset,
            ],
            'restricted' => $restricted,
            'projects' => $projects,
            'summary' => [
                ...$breakdown,
                'sales_count' => $sales->count(),
                'expense_count' => $expenses->count(),
                'salary_count' => $salaries->count(),
            ],
            'expenses_by_currency' => $expensesByCurrency,
            'incomes_by_currency' => $incomesByCurrency,
            'by_category' => $byCategory,
            'partners' => $partners,
            'sales' => $restricted ? [] : $sales,
            'expenses' => $restricted ? [] : $expenses,
            'salaries' => $restricted ? [] : $salaries,
            'activity' => $restricted ? [] : $activity,
        ];
    }
}
