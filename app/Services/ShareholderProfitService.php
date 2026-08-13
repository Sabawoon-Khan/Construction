<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ShareholderProfitService
{
    public static function netProfit(?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $projectId = null): float
    {
        $salesQuery = Sale::query()->where('sales.status', '!=', 'cancelled');

        if ($from && $to) {
            $salesQuery->whereBetween('sales.contract_date', [$from->toDateString(), $to->toDateString()]);
        }

        if ($projectId) {
            $salesQuery->where('sales.project_id', $projectId);
        }

        $salesTotal = Sale::sumTotalPrice($salesQuery);

        $expensesQuery = Expense::query()->where('status', 'approved');

        if ($from && $to) {
            $expensesQuery->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        }

        if ($projectId) {
            $expensesQuery->where('project_id', $projectId);
        }

        $expenseTotal = (float) $expensesQuery->sum('amount');

        $incomesQuery = Income::query()->where('status', 'approved');

        if ($projectId) {
            $incomesQuery->where('project_id', $projectId);
        } else {
            $incomesQuery->whereNull('project_id');
        }

        if ($from && $to) {
            $incomesQuery->whereBetween('income_date', [$from->toDateString(), $to->toDateString()]);
        }

        $incomeTotal = (float) $incomesQuery->sum('amount');

        return (float) $salesTotal + $incomeTotal - $expenseTotal;
    }

    public static function distributionsTotal(Partner $partner, int $projectId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): float
    {
        $query = $partner->distributions()->where('project_id', $projectId);

        if ($from && $to) {
            $query->whereBetween('distributed_at', [$from->toDateString(), $to->toDateString()]);
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
