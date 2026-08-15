<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ShareholderProfitService
{
    /**
     * @return array{sales_total: float, income_total: float, revenue_total: float, expense_total: float, salary_total: float, operating_expense_total: float, net_profit: float}
     */
    public static function breakdown(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): array
    {
        $salesQuery = Sale::query()->where('sales.status', '!=', 'cancelled');

        if ($from && $to) {
            $salesQuery->whereDate('sales.contract_date', '>=', $from->toDateString())
                ->whereDate('sales.contract_date', '<=', $to->toDateString());
        }

        if ($projectId) {
            $salesQuery->where('sales.project_id', $projectId);
        }

        $salesTotal = Sale::sumTotalPrice($salesQuery);
        $expenseTotal = self::expenseTotal($from, $to, $projectId);
        $salaryTotal = self::salaryTotal($from, $to, $projectId);
        $incomeTotal = self::incomeTotal($from, $to, $projectId);
        $revenueTotal = $salesTotal + $incomeTotal;

        return [
            'sales_total' => $salesTotal,
            'income_total' => $incomeTotal,
            'revenue_total' => $revenueTotal,
            'expense_total' => $expenseTotal,
            'salary_total' => $salaryTotal,
            'operating_expense_total' => max(0, $expenseTotal - $salaryTotal),
            'net_profit' => $revenueTotal - $expenseTotal,
        ];
    }

    public static function netProfit(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): float
    {
        return self::breakdown($from, $to, $projectId)['net_profit'];
    }

    public static function expenseTotal(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): float
    {
        $query = Expense::query()->where('status', 'approved');

        if ($from && $to) {
            $query->whereDate('expense_date', '>=', $from->toDateString())
                ->whereDate('expense_date', '<=', $to->toDateString());
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount');
    }

    public static function salaryTotal(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): float
    {
        $ids = ExpenseCategory::salaryIds();

        if ($ids === []) {
            return 0.0;
        }

        $query = Expense::query()
            ->where('status', 'approved')
            ->whereIn('expense_category_id', $ids);

        if ($from && $to) {
            $query->whereDate('expense_date', '>=', $from->toDateString())
                ->whereDate('expense_date', '<=', $to->toDateString());
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount');
    }

    public static function incomeTotal(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): float
    {
        $query = Income::query()->where('status', 'approved');

        if ($projectId) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }

        if ($from && $to) {
            $query->whereDate('income_date', '>=', $from->toDateString())
                ->whereDate('income_date', '<=', $to->toDateString());
        }

        return (float) $query->sum('amount');
    }

    public static function distributionsTotal(Partner $partner, int $projectId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): float
    {
        $query = $partner->distributions()->where('project_id', $projectId);

        if ($from && $to) {
            $query->whereDate('distributed_at', '>=', $from->toDateString())
                ->whereDate('distributed_at', '<=', $to->toDateString());
        }

        return (float) $query->sum('amount');
    }

    /**
     * @return array{entitled_share: float, paid_total: float, balance_owed: float, ownership_percentage: float}
     */
    public static function partnerShare(Partner $partner, int $projectId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $ownership = $partner->ownershipOnProject($projectId);
        $netProfit = self::netProfit($from, $to, $projectId);
        $entitled = ($ownership / 100) * $netProfit;
        $paid = self::distributionsTotal($partner, $projectId, $from, $to);

        return [
            'ownership_percentage' => round($ownership, 4),
            'entitled_share' => round($entitled, 2),
            'paid_total' => round($paid, 2),
            'balance_owed' => round($entitled - $paid, 2),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function projectPartners(Project $project, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return $project->partners()
            ->orderByDesc('project_partner.ownership_percentage')
            ->get()
            ->map(function (Partner $partner) use ($project, $from, $to) {
                $share = self::partnerShare($partner, $project->id, $from, $to);

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'phone' => $partner->phone,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'ownership_percentage' => $share['ownership_percentage'],
                    'contributed_total' => (float) $partner->investments()
                        ->where('project_id', $project->id)
                        ->sum('amount'),
                    'entitled_share' => $share['entitled_share'],
                    'paid_total' => $share['paid_total'],
                    'balance_owed' => $share['balance_owed'],
                    'period_profit_share' => $share['entitled_share'],
                    'profit_share' => $share['entitled_share'],
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function allPartners(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): Collection
    {
        if ($projectId) {
            $project = Project::query()->findOrFail($projectId);

            return self::projectPartners($project, $from, $to);
        }

        return Project::query()
            ->with(['partners'])
            ->get()
            ->flatMap(fn (Project $project) => self::projectPartners($project, $from, $to))
            ->values();
    }
}
