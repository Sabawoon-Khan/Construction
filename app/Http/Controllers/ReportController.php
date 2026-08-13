<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\InventoryTransaction;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Sale;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $from = $request->filled('from')
            ? Carbon::parse($request->string('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to'))->endOfDay()
            : now()->endOfDay();
        $projectId = $request->integer('project_id') ?: null;

        $expenses = Expense::query()
            ->with('category:id,name')
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->get();

        $issuedMaterials = InventoryTransaction::query()
            ->where('type', 'issue')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->where(function ($query) {
                $query->whereNull('notes')
                    ->orWhere('notes', 'not like', 'Auto-issued from direct-to-project purchase.%');
            })
            ->get();

        $sales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,currency'])
            ->where('status', '!=', 'cancelled')
            ->whereBetween('contract_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->get();

        $incomes = Income::query()
            ->where('status', 'approved')
            ->whereBetween('income_date', [$from->toDateString(), $to->toDateString()])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->get();

        $cashExpenseTotal = (float) $expenses->sum('amount');
        $issuedMaterialTotal = (float) $issuedMaterials->sum('total_cost');
        $expenseTotal = $projectId
            ? $cashExpenseTotal + $issuedMaterialTotal
            : $cashExpenseTotal;

        $salesTotal = (float) $sales->sum(fn (Sale $sale) => $sale->total_price);
        $incomeTotal = (float) $incomes->sum('amount');
        $netProfit = $salesTotal + $incomeTotal - $expenseTotal;

        $partners = Partner::query()
            ->orderByDesc('ownership_percentage')
            ->get()
            ->map(fn (Partner $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'ownership_percentage' => (float) $p->ownership_percentage,
                'profit_share' => round(((float) $p->ownership_percentage / 100) * $netProfit, 2),
            ]);

        $byCategory = $expenses
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($group, $name) => [
                'category' => $name,
                'amount' => (float) $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->values();

        if ($issuedMaterialTotal > 0) {
            $byCategory->push([
                'category' => 'Materials used (stock)',
                'amount' => $issuedMaterialTotal,
                'count' => $issuedMaterials->count(),
            ]);
        }

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'project_id' => $projectId,
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'sales_total' => $salesTotal,
                'income_total' => $incomeTotal,
                'expense_total' => $expenseTotal,
                'cash_expense_total' => $cashExpenseTotal,
                'materials_used_total' => $issuedMaterialTotal,
                'net_profit' => $netProfit,
                'sales_count' => $sales->count(),
                'expense_count' => $expenses->count() + $issuedMaterials->count(),
            ],
            'by_category' => $byCategory,
            'partners' => $partners,
            'sales' => $sales,
            'expenses' => $expenses->take(50),
            'dashboard' => DashboardService::forUser($request->user())->summary($from, $to),
        ]);
    }
}
