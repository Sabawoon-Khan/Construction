<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ContactMessage;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\FieldReport;
use App\Models\Income;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrencyAmounts;
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

        $periodSalesQuery = (clone $salesQuery)
            ->whereDate('sales.contract_date', '>=', $from->toDateString())
            ->whereDate('sales.contract_date', '<=', $to->toDateString());

        $periodSales = Sale::sumTotalPriceBase(clone $periodSalesQuery);
        $salesByCurrency = Sale::sumTotalPriceByCurrency(clone $periodSalesQuery);

        $totalSales = Sale::sumTotalPriceBase(clone $salesQuery);

        $expensesQuery = Expense::query()->where('status', 'approved');

        $periodExpensesQuery = (clone $expensesQuery)
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString());
        $periodExpenses = (clone $periodExpensesQuery)->sum('amount_base');
        $totalExpenses = (clone $expensesQuery)->sum('amount_base');
        $expensesByCurrency = CurrencyAmounts::sum($periodExpensesQuery);

        $incomesQuery = Income::query()->where('status', 'approved');
        $periodIncomesQuery = (clone $incomesQuery)
            ->whereDate('income_date', '>=', $from->toDateString())
            ->whereDate('income_date', '<=', $to->toDateString());
        $periodIncomes = (clone $periodIncomesQuery)->sum('amount_base');
        $incomesByCurrency = CurrencyAmounts::sum($periodIncomesQuery);

        $periodSalaries = ShareholderProfitService::salaryTotal($from, $to);
        $totalSalaries = ShareholderProfitService::salaryTotal();
        $periodRevenue = (float) $periodSales + (float) $periodIncomes;
        $revenueByCurrency = CurrencyAmounts::add($salesByCurrency, $incomesByCurrency);

        // Match ShareholderProfitService: company incomes are non-project only.
        $profitIncomeQuery = Income::query()
            ->where('status', 'approved')
            ->whereNull('project_id')
            ->whereDate('income_date', '>=', $from->toDateString())
            ->whereDate('income_date', '<=', $to->toDateString());
        $profitByCurrency = CurrencyAmounts::net(
            CurrencyAmounts::add($salesByCurrency, CurrencyAmounts::sum($profitIncomeQuery)),
            $expensesByCurrency,
        );

        $receiptsQuery = Payment::query()
            ->where('type', 'receipt')
            ->where('status', 'posted')
            ->whereBetween('paid_at', [$from, $to]);
        $receipts = (clone $receiptsQuery)->sum('amount_base');
        $receiptsByCurrency = CurrencyAmounts::sum($receiptsQuery);

        $netProfitPeriod = ShareholderProfitService::netProfit($from, $to);
        $netProfitTotal = ShareholderProfitService::netProfit();

        $partners = ShareholderProfitService::allPartners($from, $to);

        $recentSales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,list_price_base', 'project:id,name'])
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
            ->selectRaw('sales.project_id, SUM(units.list_price_base - COALESCE(sales.discount_base, 0)) as total')
            ->groupBy('sales.project_id')
            ->pluck('total', 'sales.project_id');

        $projects = Project::query()
            ->withCount(['units', 'sales', 'expenses'])
            ->withSum(['expenses as project_expenses_sum' => fn ($q) => $q->where('status', 'approved')], 'amount_base')
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

        $alerts = $this->alerts();
        $charts = $this->chartSeries($from, $to, $expensesByCurrency, $projects);

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
                'sales_by_currency' => $salesByCurrency,
                'expenses_by_currency' => $expensesByCurrency,
                'incomes_by_currency' => $incomesByCurrency,
                'revenue_by_currency' => $revenueByCurrency,
                'receipts_by_currency' => $receiptsByCurrency,
                'profit_by_currency' => $profitByCurrency,
                'overdue_installments_count' => $alerts['overdue_installments_count'],
                'overdue_installments_amount' => $alerts['overdue_installments_amount'],
                'purchases_owed_count' => $alerts['purchases_owed_count'],
                'purchases_owed_amount' => $alerts['purchases_owed_amount'],
                'unread_contacts' => $alerts['unread_contacts'],
                'pending_salaries_count' => $alerts['pending_salaries_count'],
                'pending_salaries_amount' => $alerts['pending_salaries_amount'],
                'attendance_today' => $alerts['attendance_today'],
                'employees_count' => $alerts['employees_count'],
                'low_stock_count' => count($alerts['low_stock']),
                'field_reports_draft' => $alerts['field_reports_draft'],
            ],
            'charts' => $charts,
            'partners' => $partners,
            'recent_sales' => $recentSales,
            'recent_expenses' => $recentExpenses,
            'projects' => $projects,
            'low_stock' => $alerts['low_stock'],
            'overdue_installments' => $alerts['overdue_installments'],
            'unpaid_purchases' => $alerts['unpaid_purchases'],
            'recent_field_reports' => $alerts['recent_field_reports'],
        ];
    }

    /**
     * @param  array<string, float>  $expensesByCurrency
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $projects
     * @return array{
     *     monthly: array{labels: list<string>, sales: list<float>, expenses: list<float>},
     *     expenses_by_currency: array<string, float>,
     *     projects: array{labels: list<string>, profit: list<float>, revenue: list<float>}
     * }
     */
    protected function chartSeries(
        CarbonInterface $from,
        CarbonInterface $to,
        array $expensesByCurrency,
        $projects,
    ): array {
        $monthly = $this->monthlyMoneySeries($from, $to);

        $projectRows = collect($projects)
            ->sortByDesc(fn (array $row) => (float) ($row['profit'] ?? 0))
            ->take(6)
            ->values();

        return [
            'monthly' => $monthly,
            'expenses_by_currency' => $expensesByCurrency,
            'projects' => [
                'labels' => $projectRows->map(fn (array $row) => (string) ($row['name'] ?? '—'))->all(),
                'profit' => $projectRows->map(fn (array $row) => round((float) ($row['profit'] ?? 0), 2))->all(),
                'revenue' => $projectRows->map(fn (array $row) => round((float) ($row['revenue'] ?? 0), 2))->all(),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, sales: list<float>, expenses: list<float>}
     */
    protected function monthlyMoneySeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $chartFrom = $from->copy()->startOfMonth();
        $chartTo = $to->copy()->endOfMonth();

        if ($chartFrom->diffInMonths($chartTo) < 1) {
            $chartFrom = $to->copy()->subMonths(5)->startOfMonth();
            $chartTo = $to->copy()->endOfMonth();
        }

        // Long ranges (e.g. all-time): use yearly buckets and keep the chart readable.
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
            ];
            // CarbonImmutable: must reassign; add* does not mutate in place.
            $cursor = $useYearly ? $cursor->addYear() : $cursor->addMonth();
        }

        // Build totals in PHP to stay dialect-safe (sqlite/mysql).
        $sales = Sale::query()
            ->with(['unit:id,list_price_base'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $chartFrom->toDateString())
            ->whereDate('contract_date', '<=', $chartTo->toDateString())
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

        foreach ($buckets as &$bucket) {
            $bucket['sales'] = round($bucket['sales'], 2);
            $bucket['expenses'] = round($bucket['expenses'], 2);
        }
        unset($bucket);

        return [
            'labels' => array_column(array_values($buckets), 'label'),
            'sales' => array_column(array_values($buckets), 'sales'),
            'expenses' => array_column(array_values($buckets), 'expenses'),
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
                'ownership_percentage' => (float) $project->pivot->ownership_percentage,
                ...$share,
            ];
        })->values();

        $allTimeShares = $partner->projects->map(fn ($project) => ShareholderProfitService::partnerShare($partner, $project->id));

        $avgOwnership = $partner->projects->count()
            ? round((float) $partner->projects->avg(fn ($p) => (float) $p->pivot->ownership_percentage), 2)
            : 0.0;

        return [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'ownership_percentage' => $avgOwnership,
                'projects' => $partner->projects->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'ownership_percentage' => (float) $p->pivot->ownership_percentage,
                ])->values(),
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
                'period_receipts' => $summary['kpis']['period_receipts'],
                'active_projects' => $summary['kpis']['active_projects'],
                'units_available' => $summary['kpis']['units_available'],
                'units_reserved' => $summary['kpis']['units_reserved'],
                'units_sold' => $summary['kpis']['units_sold'],
            ],
        ];
    }

    /**
     * @return array{
     *     overdue_installments_count: int,
     *     overdue_installments_amount: float,
     *     overdue_installments: list<array<string, mixed>>,
     *     purchases_owed_count: int,
     *     purchases_owed_amount: float,
     *     unpaid_purchases: list<array<string, mixed>>,
     *     unread_contacts: int,
     *     pending_salaries_count: int,
     *     pending_salaries_amount: float,
     *     attendance_today: int,
     *     employees_count: int,
     *     low_stock: list<array<string, mixed>>,
     *     field_reports_draft: int,
     *     recent_field_reports: list<array<string, mixed>>
     * }
     */
    protected function alerts(): array
    {
        $today = now()->toDateString();

        $overdueQuery = SaleInstallment::query()
            ->whereDate('due_date', '<', $today)
            ->whereColumn('paid_amount_base', '<', 'amount_base')
            ->whereHas('sale', fn ($q) => $q->where('status', '!=', 'cancelled'));

        $overdueInstallments = (clone $overdueQuery)
            ->with(['sale:id,sale_no,customer_id,project_id', 'sale.customer:id,name', 'sale.project:id,name'])
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(fn (SaleInstallment $row) => [
                'id' => $row->id,
                'sale_id' => $row->sale_id,
                'sale_no' => $row->sale?->sale_no,
                'customer' => $row->sale?->customer?->name,
                'project' => $row->sale?->project?->name,
                'due_date' => $row->due_date?->toDateString(),
                'currency' => $row->currency,
                'remaining' => max(0, (float) $row->amount_base - (float) $row->paid_amount_base),
                'remaining_amount' => max(0, (float) $row->amount - (float) $row->paid_amount),
            ])
            ->values()
            ->all();

        $purchasesOwedAmount = (float) Purchase::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total_amount_base - paid_amount_base), 0) as owed')
            ->value('owed');

        $purchasesOwedCount = Purchase::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->count();

        $unpaidPurchases = Purchase::query()
            ->with(['supplier:id,name', 'project:id,name'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderByDesc('purchase_date')
            ->limit(6)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'purchase_no' => $purchase->purchase_no,
                'supplier' => $purchase->supplier?->name,
                'project' => $purchase->project?->name,
                'currency' => $purchase->currency,
                'remaining' => max(0, (float) $purchase->total_amount_base - (float) $purchase->paid_amount_base),
                'remaining_amount' => max(0, (float) $purchase->total_amount - (float) $purchase->paid_amount),
                'status' => $purchase->status,
            ])
            ->values()
            ->all();

        $materials = Material::query()
            ->get(['id', 'name', 'code', 'minimum_stock'])
            ->keyBy('id');

        $onHandByMaterial = InventoryTransaction::stockLevels()
            ->groupBy('material_id')
            ->map(fn ($rows) => round((float) $rows->sum('on_hand'), 3));

        $lowStock = $materials
            ->map(function (Material $material) use ($onHandByMaterial) {
                $minimum = (float) $material->minimum_stock;
                if ($minimum <= 0) {
                    return null;
                }

                $onHand = (float) ($onHandByMaterial[$material->id] ?? 0);
                if ($onHand >= $minimum) {
                    return null;
                }

                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'code' => $material->code,
                    'on_hand' => $onHand,
                    'minimum_stock' => $minimum,
                ];
            })
            ->filter()
            ->sortBy('on_hand')
            ->take(6)
            ->values()
            ->all();

        $pendingSalaries = Salary::query()->where('status', 'pending');

        $recentFieldReports = FieldReport::query()
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'location', 'updated_at'])
            ->map(fn (FieldReport $report) => [
                'id' => $report->id,
                'title' => $report->title,
                'status' => $report->status,
                'location' => $report->location,
                'updated_at' => $report->updated_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        return [
            'overdue_installments_count' => (clone $overdueQuery)->count(),
            'overdue_installments_amount' => (float) (clone $overdueQuery)->selectRaw(
                'COALESCE(SUM(amount_base - paid_amount_base), 0) as owed'
            )->value('owed'),
            'overdue_installments' => $overdueInstallments,
            'purchases_owed_count' => $purchasesOwedCount,
            'purchases_owed_amount' => $purchasesOwedAmount,
            'unpaid_purchases' => $unpaidPurchases,
            'unread_contacts' => ContactMessage::query()->unread()->count(),
            'pending_salaries_count' => (clone $pendingSalaries)->count(),
            'pending_salaries_amount' => (float) (clone $pendingSalaries)->sum('net_salary_base'),
            'attendance_today' => Attendance::query()->whereDate('date', $today)->count(),
            'employees_count' => Employee::query()->count(),
            'low_stock' => $lowStock,
            'field_reports_draft' => FieldReport::query()->where('status', 'draft')->count(),
            'recent_field_reports' => $recentFieldReports,
        ];
    }

    public static function forUser(User $user): self
    {
        return new self;
    }
}
