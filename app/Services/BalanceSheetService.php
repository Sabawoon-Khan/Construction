<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Voucher;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Build a balance sheet (assets, liabilities, equity) as of a given date.
 *
 * All amounts are reported in the company base currency using stored
 * `amount_base` / `*_base` columns and exchange rates. This avoids runtime
 * conversion and makes mixed-currency reporting consistent.
 */
class BalanceSheetService
{
    private const EPOCH_START_YEAR = 1900;

    /**
     * @return array<string, mixed>
     */
    public static function asOf(?CarbonInterface $asOf = null, ?int $projectId = null): array
    {
        $asOf ??= Carbon::now()->endOfDay();

        $cash = self::cashAndBank($asOf, $projectId);
        $receivables = self::accountsReceivable($asOf, $projectId);
        $inventory = self::inventoryValue($asOf, $projectId);
        $payables = self::accountsPayable($asOf, $projectId);
        $distributionsPayable = self::distributionsPayable($asOf, $projectId);
        $equity = self::equity($asOf, $projectId);

        // Inventory is tracked physically but expensed at purchase, so it is shown
        // as a memo item rather than included in the formal balance-sheet totals.
        $totalCurrentAssets = round($cash['total'] + $receivables['total'], 2);
        $totalAssets = $totalCurrentAssets;

        $totalCurrentLiabilities = round($payables['total'] + $distributionsPayable['total'], 2);
        $totalLiabilities = $totalCurrentLiabilities;

        $totalEquity = round($equity['total'], 2);
        $balanceCheck = round($totalAssets - $totalLiabilities - $totalEquity, 2);

        $workingCapital = round($totalCurrentAssets - $totalCurrentLiabilities, 2);
        $currentRatio = $totalCurrentLiabilities > 0
            ? round($totalCurrentAssets / $totalCurrentLiabilities, 2)
            : 0;

        return [
            'as_of' => $asOf->toDateString(),
            'project_id' => $projectId,
            'assets' => [
                'cash_and_bank' => $cash,
                'accounts_receivable' => $receivables,
                'total_current' => $totalCurrentAssets,
                'total' => $totalAssets,
            ],
            'memo' => [
                'inventory' => $inventory,
            ],
            'liabilities' => [
                'accounts_payable' => $payables,
                'distributions_payable' => $distributionsPayable,
                'total_current' => $totalCurrentLiabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => $equity,
            'metrics' => [
                'working_capital' => $workingCapital,
                'current_ratio' => $currentRatio,
                'balance_check' => $balanceCheck,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function cashAndBank(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $accounts = BankAccount::query()
            ->orderBy('is_cash')
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'opening_balance', 'opening_balance_base', 'is_cash', 'currency'])
            ->map(function (BankAccount $account) use ($asOf, $projectId) {
                $balance = self::bankAccountBalance($account, $asOf, $projectId);

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'bank_name' => $account->bank_name,
                    'is_cash' => $account->is_cash,
                    'currency' => $account->currency,
                    'opening_balance' => (float) $account->opening_balance,
                    'opening_balance_base' => (float) $account->opening_balance_base,
                    'balance' => $balance,
                ];
            });

        $bankTotal = $accounts->where('is_cash', false)->sum('balance');
        $cashOnHand = $accounts->where('is_cash', true)->sum('balance') + self::unlinkedCashPosition($asOf, $projectId);

        $total = round($bankTotal + $cashOnHand, 2);

        return [
            'accounts' => $accounts->values(),
            'bank_total' => round($bankTotal, 2),
            'cash_on_hand' => round($cashOnHand, 2),
            'total' => $total,
        ];
    }

    public static function bankAccountBalance(BankAccount $account, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $receipts = self::sumPaymentQuery(
            Payment::query()->where('bank_account_id', $account->id)->where('type', 'receipt'),
            $asOf,
            $projectId,
        );

        $disbursements = self::sumPaymentQuery(
            Payment::query()->where('bank_account_id', $account->id)->where('type', 'disbursement'),
            $asOf,
            $projectId,
        );

        $incomes = self::sumIncomeQuery(
            Income::query()->where('bank_account_id', $account->id),
            $asOf,
            $projectId,
        );

        $vouchers = self::sumVoucherQuery(
            Voucher::query()->where('bank_account_id', $account->id)->where('status', 'approved'),
            $asOf,
            $projectId,
        );

        return round(
            (float) $account->opening_balance_base + $receipts - $disbursements + $incomes - $vouchers,
            2,
        );
    }

    public static function unlinkedCashPosition(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $receipts = self::sumPaymentQuery(
            Payment::query()->whereNull('bank_account_id')->where('type', 'receipt'),
            $asOf,
            $projectId,
        );

        $disbursements = self::sumPaymentQuery(
            Payment::query()->whereNull('bank_account_id')->where('type', 'disbursement'),
            $asOf,
            $projectId,
        );

        $incomes = self::sumIncomeQuery(
            Income::query()->whereNull('bank_account_id'),
            $asOf,
            $projectId,
        );

        $vouchers = self::sumVoucherQuery(
            Voucher::query()->whereNull('bank_account_id')->where('status', 'approved'),
            $asOf,
            $projectId,
        );

        $directExpenses = (float) Expense::query()
            ->where('status', 'approved')
            ->whereNull('purchase_id')
            ->whereDate('expense_date', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId))
            ->sum('amount_base');

        $partnerInvestments = self::sumPartnerInvestments($asOf, $projectId);
        $partnerDistributions = self::sumPartnerDistributions($asOf, $projectId);

        return round(
            $receipts - $disbursements + $incomes - $vouchers - $directExpenses + $partnerInvestments - $partnerDistributions,
            2,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function accountsReceivable(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $sales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,list_price_base', 'project:id,name'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId))
            ->get();

        $customerBalances = [];

        foreach ($sales as $sale) {
            $total = $sale->total_price_base;
            $paid = (float) Payment::query()
                ->where('sale_id', $sale->id)
                ->where('type', 'receipt')
                ->whereNotNull('paid_at')
                ->where('paid_at', '<=', $asOf)
                ->sum('amount_base');
            $remaining = max(0, round($total - $paid, 2));

            if ($remaining <= 0) {
                continue;
            }

            $customerId = $sale->customer_id;
            $customerName = $sale->customer?->name ?? __('Unknown customer');

            if (! isset($customerBalances[$customerId])) {
                $customerBalances[$customerId] = [
                    'id' => $customerId,
                    'name' => $customerName,
                    'amount' => 0.0,
                    'details' => [],
                ];
            }

            $customerBalances[$customerId]['amount'] += $remaining;
            $customerBalances[$customerId]['details'][] = [
                'sale_id' => $sale->id,
                'sale_no' => $sale->sale_no,
                'project_name' => $sale->project?->name,
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remaining,
            ];
        }

        $items = collect($customerBalances)
            ->sortByDesc('amount')
            ->values()
            ->map(fn (array $row) => [
                ...$row,
                'amount' => round($row['amount'], 2),
            ]);

        return [
            'items' => $items,
            'total' => round($items->sum('amount'), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function accountsPayable(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'project:id,name'])
            ->whereDate('purchase_date', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId))
            ->get();

        $supplierBalances = [];

        foreach ($purchases as $purchase) {
            $total = (float) $purchase->total_amount_base;
            $paid = (float) $purchase->payments()
                ->whereNotNull('paid_at')
                ->where('paid_at', '<=', $asOf)
                ->sum('amount_base');
            $remaining = max(0, round($total - $paid, 2));

            if ($remaining <= 0) {
                continue;
            }

            $supplierId = $purchase->supplier_id;
            $supplierName = $purchase->supplier?->name ?? __('Unknown supplier');

            if (! isset($supplierBalances[$supplierId])) {
                $supplierBalances[$supplierId] = [
                    'id' => $supplierId,
                    'name' => $supplierName,
                    'amount' => 0.0,
                    'details' => [],
                ];
            }

            $supplierBalances[$supplierId]['amount'] += $remaining;
            $supplierBalances[$supplierId]['details'][] = [
                'purchase_id' => $purchase->id,
                'purchase_no' => $purchase->purchase_no,
                'project_name' => $purchase->project?->name,
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remaining,
            ];
        }

        $items = collect($supplierBalances)
            ->sortByDesc('amount')
            ->values()
            ->map(fn (array $row) => [
                ...$row,
                'amount' => round($row['amount'], 2),
            ]);

        return [
            'items' => $items,
            'total' => round($items->sum('amount'), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function inventoryValue(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $query = InventoryTransaction::query()
            ->select(['project_id', 'material_id'])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as purchased', [InventoryTransaction::TYPE_PURCHASE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as issued', [InventoryTransaction::TYPE_ISSUE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN total_cost_base ELSE 0 END), 0) as purchased_cost_base', [InventoryTransaction::TYPE_PURCHASE])
            ->whereDate('transaction_date', '<=', $asOf->toDateString())
            ->groupBy('project_id', 'material_id');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $rows = $query->get();
        $materials = Material::query()->pluck('unit_cost_base', 'id');
        $projects = Project::query()->pluck('name', 'id');
        $materialNames = Material::query()->pluck('name', 'id');

        $items = $rows
            ->map(function ($row) use ($materials, $projects, $materialNames) {
                $onHand = max(0, round((float) $row->purchased - (float) $row->issued, 3));

                if ($onHand <= 0) {
                    return null;
                }

                $avgCost = (float) $row->purchased > 0
                    ? round((float) $row->purchased_cost_base / (float) $row->purchased, 2)
                    : (float) ($materials[$row->material_id] ?? 0);

                $value = round($onHand * $avgCost, 2);

                return [
                    'project_id' => $row->project_id,
                    'project_name' => $projects[$row->project_id] ?? null,
                    'material_id' => $row->material_id,
                    'material_name' => $materialNames[$row->material_id] ?? __('Unknown material'),
                    'on_hand' => $onHand,
                    'unit_cost' => $avgCost,
                    'value' => $value,
                ];
            })
            ->filter()
            ->sortByDesc('value')
            ->values();

        return [
            'items' => $items,
            'total' => round($items->sum('value'), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function distributionsPayable(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $partners = Partner::query()
            ->with(['projects:id,name'])
            ->get();

        $items = collect();
        $epoch = self::epoch($asOf);

        foreach ($partners as $partner) {
            $projects = $projectId
                ? $partner->projects->where('id', $projectId)
                : $partner->projects;

            foreach ($projects as $project) {
                $share = ShareholderProfitService::partnerShare($partner, $project->id, $epoch, $asOf);
                $owed = max(0, round($share['entitled_share'] - $share['paid_total'], 2));

                if ($owed <= 0) {
                    continue;
                }

                $items->push([
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'ownership_percentage' => $share['ownership_percentage'],
                    'entitled_share' => $share['entitled_share'],
                    'paid_total' => $share['paid_total'],
                    'amount' => $owed,
                ]);
            }
        }

        return [
            'items' => $items->sortByDesc('amount')->values(),
            'total' => round($items->sum('amount'), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function equity(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $investmentsQuery = PartnerInvestment::query()
            ->whereDate('invested_at', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

        $investmentsTotal = (float) $investmentsQuery->sum('amount_base');

        $investmentsByPartner = Partner::query()
            ->with(['projects:id,name'])
            ->get()
            ->map(function (Partner $partner) use ($asOf, $projectId) {
                $query = $partner->investments()
                    ->whereDate('invested_at', '<=', $asOf->toDateString())
                    ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

                $total = (float) $query->sum('amount_base');

                $byProject = $query
                    ->select('project_id', DB::raw('SUM(amount_base) as amount'))
                    ->groupBy('project_id')
                    ->get()
                    ->map(fn ($row) => [
                        'project_id' => $row->project_id,
                        'project_name' => Project::query()->whereKey($row->project_id)->value('name'),
                        'amount' => (float) $row->amount,
                    ]);

                return [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'amount' => $total,
                    'by_project' => $byProject->values(),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0 || $row['by_project']->isNotEmpty())
            ->sortByDesc('amount')
            ->values();

        $netProfit = self::netProfitToDate($asOf, $projectId);
        $distributionsPaid = self::sumPartnerDistributions($asOf, $projectId);
        $distributionsPayable = self::distributionsPayable($asOf, $projectId)['total'];
        $retainedEarnings = round($netProfit - $distributionsPaid - $distributionsPayable, 2);

        $total = round($investmentsTotal + $retainedEarnings, 2);

        return [
            'partner_capital' => [
                'items' => $investmentsByPartner,
                'total' => round($investmentsTotal, 2),
            ],
            'retained_earnings' => $retainedEarnings,
            'distributions_payable' => $distributionsPayable,
            'total' => $total,
        ];
    }

    public static function netProfitToDate(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $salesQuery = Sale::query()
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '<=', $asOf->toDateString());

        $incomeQuery = Income::query()
            ->where('status', 'approved')
            ->whereDate('income_date', '<=', $asOf->toDateString());

        $expenseQuery = Expense::query()
            ->where('status', 'approved')
            ->whereDate('expense_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $salesQuery->where('project_id', $projectId);
            $incomeQuery->where('project_id', $projectId);
            $expenseQuery->where('project_id', $projectId);
        }

        $salesTotal = Sale::sumTotalPriceBase($salesQuery);
        $incomeTotal = (float) $incomeQuery->sum('amount_base');
        $expenseTotal = (float) $expenseQuery->sum('amount_base');

        return round($salesTotal + $incomeTotal - $expenseTotal, 2);
    }

    public static function sumPartnerInvestments(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query = PartnerInvestment::query()
            ->whereDate('invested_at', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

        return (float) $query->sum('amount_base');
    }

    public static function sumPartnerDistributions(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query = PartnerDistribution::query()
            ->whereDate('distributed_at', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

        return (float) $query->sum('amount_base');
    }

    private static function epoch(CarbonInterface $asOf): CarbonInterface
    {
        return Carbon::create(self::EPOCH_START_YEAR, 1, 1, 0, 0, 0, $asOf->getTimezone());
    }

    private static function sumPaymentQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->whereNotNull('paid_at')->where('paid_at', '<=', $asOf);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }

    private static function sumIncomeQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->where('status', 'approved')->whereDate('income_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }

    private static function sumVoucherQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->whereDate('voucher_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }
}
