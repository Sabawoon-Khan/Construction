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
use Carbon\CarbonInterface;
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

        $allowedProjectIds = $restricted ? ($user->assignedProjectIds() ?: [0]) : null;

        $expenses = Expense::query()
            ->with('category:id,name,code')
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->latest('expense_date')
            ->limit(100)
            ->get();

        $salesQuery = Sale::query()
            ->where('sales.status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $from->toDateString())
            ->whereDate('contract_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds));

        $salesByCurrency = Sale::sumTotalPriceByCurrency(clone $salesQuery);

        $sales = (clone $salesQuery)
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,currency', 'project:id,name'])
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
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('expenses.project_id', $allowedProjectIds))
            ->selectRaw('expense_categories.name as category, COUNT(*) as count, SUM(expenses.amount) as amount, SUM(expenses.amount_base) as amount_base')
            ->groupBy('expense_categories.name')
            ->orderByDesc('amount_base')
            ->get();

        $expensesByCurrency = Expense::query()
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->selectRaw('currency, COALESCE(SUM(amount), 0) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $incomesByCurrency = Income::query()
            ->where('status', 'approved')
            ->whereDate('income_date', '>=', $from->toDateString())
            ->whereDate('income_date', '<=', $to->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->selectRaw('currency, COALESCE(SUM(amount), 0) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $activity = StatusChangeLog::query()
            ->with(['subject', 'changedBy:id,name'])
            ->where('subject_type', Project::class)
            ->whereBetween('created_at', [$from, $to])
            ->when($projectId, fn ($q) => $q->where('subject_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('subject_id', $allowedProjectIds))
            ->latest()
            ->limit(30)
            ->get();

        $projects = Project::query()
            ->orderBy('name')
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('id', $allowedProjectIds))
            ->get(['id', 'name']);

        $charts = $this->chartSeries($from, $to, $projectId, $allowedProjectIds);

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
            'sales_by_currency' => $salesByCurrency,
            'expenses_by_currency' => $expensesByCurrency,
            'incomes_by_currency' => $incomesByCurrency,
            'charts' => $charts,
            'by_category' => $byCategory,
            'partners' => $partners,
            'sales' => $restricted ? [] : $sales,
            'expenses' => $restricted ? [] : $expenses,
            'salaries' => $restricted ? [] : $salaries,
            'activity' => $restricted ? [] : $activity,
        ];
    }

    /**
     * @param  list<int>|null  $allowedProjectIds
     * @return array{monthly: array{labels: list<string>, sales: list<float>, expenses: list<float>, income: list<float>}}
     */
    private function chartSeries(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $projectId,
        ?array $allowedProjectIds,
    ): array {
        $chartFrom = $from->copy()->startOfMonth();
        $chartTo = $to->copy()->endOfMonth();

        if ($chartFrom->diffInMonths($chartTo) < 1) {
            $chartFrom = $to->copy()->subMonths(5)->startOfMonth();
            $chartTo = $to->copy()->endOfMonth();
        }

        $useYearly = $chartFrom->diffInMonths($chartTo) >= 18;
        if ($useYearly && $chartFrom->diffInYears($chartTo) > 8) {
            $chartFrom = $to->copy()->subYears(7)->startOfYear();
            $chartTo = $to->copy()->endOfYear();
        }

        $buckets = [];
        $cursor = $useYearly ? $chartFrom->copy()->startOfYear() : $chartFrom->copy();
        while ($cursor->lte($chartTo)) {
            $key = $useYearly ? $cursor->format('Y') : $cursor->format('Y-m');
            $buckets[$key] = [
                'label' => $useYearly ? $cursor->format('Y') : $cursor->format('M Y'),
                'sales' => 0.0,
                'expenses' => 0.0,
                'income' => 0.0,
            ];
            $cursor = $useYearly ? $cursor->addYear() : $cursor->addMonth();
        }

        $sales = Sale::query()
            ->with(['unit:id,list_price_base'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $chartFrom->toDateString())
            ->whereDate('contract_date', '<=', $chartTo->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->get(['id', 'unit_id', 'contract_date', 'discount_base']);

        foreach ($sales as $sale) {
            $date = $sale->contract_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['sales'] += max(
                0,
                (float) ($sale->unit?->list_price_base ?? 0) - (float) ($sale->discount_base ?? 0),
            );
        }

        $expenses = Expense::query()
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $chartFrom->toDateString())
            ->whereDate('expense_date', '<=', $chartTo->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->get(['expense_date', 'amount_base']);

        foreach ($expenses as $expense) {
            $date = $expense->expense_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['expenses'] += (float) ($expense->amount_base ?? 0);
        }

        $incomes = Income::query()
            ->where('status', 'approved')
            ->whereDate('income_date', '>=', $chartFrom->toDateString())
            ->whereDate('income_date', '<=', $chartTo->toDateString())
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($allowedProjectIds !== null, fn ($q) => $q->whereIn('project_id', $allowedProjectIds))
            ->when($projectId === null && $allowedProjectIds === null, fn ($q) => $q->whereNull('project_id'))
            ->get(['income_date', 'amount_base']);

        foreach ($incomes as $income) {
            $date = $income->income_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['income'] += (float) ($income->amount_base ?? 0);
        }

        foreach ($buckets as &$bucket) {
            $bucket['sales'] = round($bucket['sales'], 2);
            $bucket['expenses'] = round($bucket['expenses'], 2);
            $bucket['income'] = round($bucket['income'], 2);
        }
        unset($bucket);

        $rows = array_values($buckets);

        return [
            'monthly' => [
                'labels' => array_column($rows, 'label'),
                'sales' => array_column($rows, 'sales'),
                'expenses' => array_column($rows, 'expenses'),
                'income' => array_column($rows, 'income'),
            ],
        ];
    }
}
