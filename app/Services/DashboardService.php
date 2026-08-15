<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonInterface;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        $activeProjects = Project::query()
            ->whereIn('status', ['planning', 'under_construction', 'ready_for_sale'])
            ->count();

        $totalProjects = Project::query()->count();

        $units = Unit::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $salesQuery = Sale::query()->where('sales.status', '!=', 'cancelled');

        $periodSales = Sale::sumTotalPrice(
            (clone $salesQuery)
                ->whereDate('sales.contract_date', '>=', $from->toDateString())
                ->whereDate('sales.contract_date', '<=', $to->toDateString())
        );

        $totalSales = Sale::sumTotalPrice(clone $salesQuery);

        $expensesQuery = Expense::query()->where('status', 'approved');

        $periodExpenses = (clone $expensesQuery)
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->sum('amount');

        $totalExpenses = (clone $expensesQuery)->sum('amount');

        $incomesQuery = Income::query()->where('status', 'approved');

        $periodIncomes = (clone $incomesQuery)
            ->whereDate('income_date', '>=', $from->toDateString())
            ->whereDate('income_date', '<=', $to->toDateString())
            ->sum('amount');

        $periodSalaries = ShareholderProfitService::salaryTotal($from, $to);
        $totalSalaries = ShareholderProfitService::salaryTotal();
        $periodRevenue = (float) $periodSales + (float) $periodIncomes;

        $receipts = Payment::query()
            ->where('type', 'receipt')
            ->where('status', 'posted')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $netProfitPeriod = ShareholderProfitService::netProfit($from, $to);
        $netProfitTotal = ShareholderProfitService::netProfit();

        $partners = ShareholderProfitService::allPartners($from, $to);

        $recentSales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price', 'project:id,name'])
            ->latest('contract_date')
            ->limit(6)
            ->get();

        $recentExpenses = Expense::query()
            ->with(['project:id,name'])
            ->latest('expense_date')
            ->limit(6)
            ->get();

        $salesByProject = Sale::query()
            ->join('units', 'units.id', '=', 'sales.unit_id')
            ->where('sales.status', '!=', 'cancelled')
            ->selectRaw('sales.project_id, SUM(units.list_price - COALESCE(sales.discount, 0)) as total')
            ->groupBy('sales.project_id')
            ->pluck('total', 'project_id');

        $projects = Project::query()
            ->withCount(['units', 'sales', 'expenses'])
            ->withSum(['expenses as project_expenses_sum' => fn ($q) => $q->where('status', 'approved')], 'amount')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Project $project) use ($salesByProject) {
                $revenue = (float) ($salesByProject[$project->id] ?? 0);
                $cost = (float) ($project->project_expenses_sum ?? 0);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'status' => $project->status,
                    'progress_percent' => (float) $project->progress_percent,
                    'units_count' => $project->units_count,
                    'sales_count' => $project->sales_count,
                    'revenue' => $revenue,
                    'expenses' => $cost,
                    'profit' => $revenue - $cost,
                ];
            });

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'kpis' => [
                'active_projects' => $activeProjects,
                'total_projects' => $totalProjects,
                'units_available' => (int) ($units['available'] ?? 0),
                'units_sold' => (int) ($units['sold'] ?? 0),
                'units_reserved' => (int) ($units['reserved'] ?? 0),
                'period_sales' => (float) $periodSales,
                'total_sales' => (float) $totalSales,
                'period_expenses' => (float) $periodExpenses,
                'total_expenses' => (float) $totalExpenses,
                'period_incomes' => (float) $periodIncomes,
                'period_revenue' => $periodRevenue,
                'period_salaries' => $periodSalaries,
                'total_salaries' => $totalSalaries,
                'period_receipts' => (float) $receipts,
                'net_profit_period' => $netProfitPeriod,
                'net_profit_total' => $netProfitTotal,
            ],
            'partners' => $partners,
            'recent_sales' => $recentSales,
            'recent_expenses' => $recentExpenses,
            'projects' => $projects,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shareholderView(Partner $partner, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        $summary = $this->summary($from, $to);
        $partner->loadMissing('projects:id,name');

        $projectShares = $partner->projects->map(function ($project) use ($partner, $from, $to) {
            $share = ShareholderProfitService::partnerShare($partner, $project->id, $from, $to);

            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                ...$share,
            ];
        });

        $allTimeShares = $partner->projects->map(fn ($project) => ShareholderProfitService::partnerShare($partner, $project->id));

        return [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'projects' => $partner->projects->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'ownership_percentage' => (float) $p->pivot->ownership_percentage,
                ]),
            ],
            'company_profit' => $summary['kpis']['net_profit_period'],
            'my_profit' => round((float) $projectShares->sum('entitled_share'), 2),
            'my_paid' => round((float) $projectShares->sum('paid_total'), 2),
            'my_balance' => round((float) $projectShares->sum('balance_owed'), 2),
            'project_shares' => $projectShares,
            'all_time' => [
                'entitled_share' => round((float) $allTimeShares->sum('entitled_share'), 2),
                'paid_total' => round((float) $allTimeShares->sum('paid_total'), 2),
                'balance_owed' => round((float) $allTimeShares->sum('balance_owed'), 2),
            ],
            'period' => $summary['period'],
            'projects' => $summary['projects'],
            'kpis' => [
                'period_sales' => $summary['kpis']['period_sales'],
                'period_revenue' => $summary['kpis']['period_revenue'],
                'period_expenses' => $summary['kpis']['period_expenses'],
                'period_salaries' => $summary['kpis']['period_salaries'],
                'active_projects' => $summary['kpis']['active_projects'],
            ],
        ];
    }

    public static function forUser(User $user): self
    {
        return new self;
    }
}
