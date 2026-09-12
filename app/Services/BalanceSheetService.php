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
use App\Support\CurrencyAmounts;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Build a balance sheet (assets, liabilities, equity) as of a given date.
 *
 * Scalar totals stay in company base currency (`*_base` columns). Parallel
 * `*_by_currency` maps keep native AFN / USD amounts like the rest of the MIS.
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
        $totalCurrentAssetsByCurrency = CurrencyAmounts::add(
            $cash['total_by_currency'],
            $receivables['total_by_currency'],
        );
        $totalAssets = $totalCurrentAssets;
        $totalAssetsByCurrency = $totalCurrentAssetsByCurrency;

        $totalCurrentLiabilities = round($payables['total'] + $distributionsPayable['total'], 2);
        $totalCurrentLiabilitiesByCurrency = CurrencyAmounts::add(
            $payables['total_by_currency'],
            $distributionsPayable['total_by_currency'],
        );
        $totalLiabilities = $totalCurrentLiabilities;
        $totalLiabilitiesByCurrency = $totalCurrentLiabilitiesByCurrency;

        $totalEquity = round($equity['total'], 2);
        $totalEquityByCurrency = $equity['total_by_currency'];
        $balanceCheck = round($totalAssets - $totalLiabilities - $totalEquity, 2);
        $balanceCheckByCurrency = CurrencyAmounts::remaining(
            $totalAssetsByCurrency,
            CurrencyAmounts::add($totalLiabilitiesByCurrency, $totalEquityByCurrency),
        );

        $workingCapital = round($totalCurrentAssets - $totalCurrentLiabilities, 2);
        $workingCapitalByCurrency = CurrencyAmounts::remaining(
            $totalCurrentAssetsByCurrency,
            $totalCurrentLiabilitiesByCurrency,
        );
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
                'total_current_by_currency' => $totalCurrentAssetsByCurrency,
                'total' => $totalAssets,
                'total_by_currency' => $totalAssetsByCurrency,
            ],
            'memo' => [
                'inventory' => $inventory,
            ],
            'liabilities' => [
                'accounts_payable' => $payables,
                'distributions_payable' => $distributionsPayable,
                'total_current' => $totalCurrentLiabilities,
                'total_current_by_currency' => $totalCurrentLiabilitiesByCurrency,
                'total' => $totalLiabilities,
                'total_by_currency' => $totalLiabilitiesByCurrency,
            ],
            'equity' => $equity,
            'metrics' => [
                'working_capital' => $workingCapital,
                'working_capital_by_currency' => $workingCapitalByCurrency,
                'current_ratio' => $currentRatio,
                'balance_check' => $balanceCheck,
                'balance_check_by_currency' => $balanceCheckByCurrency,
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
                $balanceByCurrency = self::bankAccountBalanceByCurrency($account, $asOf, $projectId);

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'bank_name' => $account->bank_name,
                    'is_cash' => $account->is_cash,
                    'currency' => $account->currency,
                    'opening_balance' => (float) $account->opening_balance,
                    'opening_balance_base' => (float) $account->opening_balance_base,
                    'balance' => $balance,
                    'balance_by_currency' => $balanceByCurrency,
                ];
            });

        $bankAccounts = $accounts->where('is_cash', false);
        $cashAccounts = $accounts->where('is_cash', true);

        $bankTotal = $bankAccounts->sum('balance');
        $bankTotalByCurrency = self::sumMaps($bankAccounts->pluck('balance_by_currency')->all());

        $unlinked = self::unlinkedCashPosition($asOf, $projectId);
        $unlinkedByCurrency = self::unlinkedCashPositionByCurrency($asOf, $projectId);

        $cashOnHand = $cashAccounts->sum('balance') + $unlinked;
        $cashOnHandByCurrency = CurrencyAmounts::add(
            self::sumMaps($cashAccounts->pluck('balance_by_currency')->all()),
            $unlinkedByCurrency,
        );

        $total = round($bankTotal + $cashOnHand, 2);
        $totalByCurrency = CurrencyAmounts::add($bankTotalByCurrency, $cashOnHandByCurrency);

        return [
            'accounts' => $accounts->values(),
            'bank_total' => round($bankTotal, 2),
            'bank_total_by_currency' => $bankTotalByCurrency,
            'cash_on_hand' => round($cashOnHand, 2),
            'cash_on_hand_by_currency' => $cashOnHandByCurrency,
            'total' => $total,
            'total_by_currency' => $totalByCurrency,
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

    /**
     * @return array<string, float>
     */
    public static function bankAccountBalanceByCurrency(BankAccount $account, CarbonInterface $asOf, ?int $projectId = null): array
    {
        $opening = self::bucket($account->currency, (float) $account->opening_balance);

        $receipts = self::sumPaymentQueryByCurrency(
            Payment::query()->where('bank_account_id', $account->id)->where('type', 'receipt'),
            $asOf,
            $projectId,
        );

        $disbursements = self::sumPaymentQueryByCurrency(
            Payment::query()->where('bank_account_id', $account->id)->where('type', 'disbursement'),
            $asOf,
            $projectId,
        );

        $incomes = self::sumIncomeQueryByCurrency(
            Income::query()->where('bank_account_id', $account->id),
            $asOf,
            $projectId,
        );

        $vouchers = self::sumVoucherQueryByCurrency(
            Voucher::query()->where('bank_account_id', $account->id)->where('status', 'approved'),
            $asOf,
            $projectId,
        );

        return CurrencyAmounts::remaining(
            CurrencyAmounts::add($opening, $receipts, $incomes),
            CurrencyAmounts::add($disbursements, $vouchers),
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
     * @return array<string, float>
     */
    public static function unlinkedCashPositionByCurrency(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $receipts = self::sumPaymentQueryByCurrency(
            Payment::query()->whereNull('bank_account_id')->where('type', 'receipt'),
            $asOf,
            $projectId,
        );

        $disbursements = self::sumPaymentQueryByCurrency(
            Payment::query()->whereNull('bank_account_id')->where('type', 'disbursement'),
            $asOf,
            $projectId,
        );

        $incomes = self::sumIncomeQueryByCurrency(
            Income::query()->whereNull('bank_account_id'),
            $asOf,
            $projectId,
        );

        $vouchers = self::sumVoucherQueryByCurrency(
            Voucher::query()->whereNull('bank_account_id')->where('status', 'approved'),
            $asOf,
            $projectId,
        );

        $directExpenses = CurrencyAmounts::sum(
            Expense::query()
                ->where('status', 'approved')
                ->whereNull('purchase_id')
                ->whereDate('expense_date', '<=', $asOf->toDateString())
                ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId)),
        );

        $partnerInvestments = self::sumPartnerInvestmentsByCurrency($asOf, $projectId);
        $partnerDistributions = self::sumPartnerDistributionsByCurrency($asOf, $projectId);

        return CurrencyAmounts::remaining(
            CurrencyAmounts::add($receipts, $incomes, $partnerInvestments),
            CurrencyAmounts::add($disbursements, $vouchers, $directExpenses, $partnerDistributions),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function accountsReceivable(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $sales = Sale::query()
            ->with(['customer:id,name', 'unit:id,unit_no,list_price,list_price_base,currency', 'project:id,name'])
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

            $totalByCurrency = self::bucket(
                $sale->currency ?: $sale->unit?->currency,
                (float) $sale->total_price,
            );
            $paidByCurrency = CurrencyAmounts::sum(
                Payment::query()
                    ->where('sale_id', $sale->id)
                    ->where('type', 'receipt')
                    ->whereNotNull('paid_at')
                    ->where('paid_at', '<=', $asOf),
            );
            $remainingByCurrency = self::maxZero(CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency));

            if ($remaining <= 0 && self::isZeroMap($remainingByCurrency)) {
                continue;
            }

            $customerId = $sale->customer_id;
            $customerName = $sale->customer?->name ?? __('Unknown customer');

            if (! isset($customerBalances[$customerId])) {
                $customerBalances[$customerId] = [
                    'id' => $customerId,
                    'name' => $customerName,
                    'amount' => 0.0,
                    'amount_by_currency' => self::empty(),
                    'details' => [],
                ];
            }

            $customerBalances[$customerId]['amount'] += $remaining;
            $customerBalances[$customerId]['amount_by_currency'] = CurrencyAmounts::add(
                $customerBalances[$customerId]['amount_by_currency'],
                $remainingByCurrency,
            );
            $customerBalances[$customerId]['details'][] = [
                'sale_id' => $sale->id,
                'sale_no' => $sale->sale_no,
                'project_name' => $sale->project?->name,
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remaining,
                'remaining_by_currency' => $remainingByCurrency,
            ];
        }

        $items = collect($customerBalances)
            ->sortByDesc('amount')
            ->values()
            ->map(fn (array $row) => [
                ...$row,
                'amount' => round($row['amount'], 2),
                'amount_by_currency' => $row['amount_by_currency'],
            ]);

        return [
            'items' => $items,
            'total' => round($items->sum('amount'), 2),
            'total_by_currency' => self::sumMaps($items->pluck('amount_by_currency')->all()),
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

            $totalByCurrency = self::bucket($purchase->currency, (float) $purchase->total_amount);
            $paidByCurrency = CurrencyAmounts::sum(
                $purchase->payments()
                    ->whereNotNull('paid_at')
                    ->where('paid_at', '<=', $asOf)
                    ->getQuery(),
            );
            $remainingByCurrency = self::maxZero(CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency));

            if ($remaining <= 0 && self::isZeroMap($remainingByCurrency)) {
                continue;
            }

            $supplierId = $purchase->supplier_id;
            $supplierName = $purchase->supplier?->name ?? __('Unknown supplier');

            if (! isset($supplierBalances[$supplierId])) {
                $supplierBalances[$supplierId] = [
                    'id' => $supplierId,
                    'name' => $supplierName,
                    'amount' => 0.0,
                    'amount_by_currency' => self::empty(),
                    'details' => [],
                ];
            }

            $supplierBalances[$supplierId]['amount'] += $remaining;
            $supplierBalances[$supplierId]['amount_by_currency'] = CurrencyAmounts::add(
                $supplierBalances[$supplierId]['amount_by_currency'],
                $remainingByCurrency,
            );
            $supplierBalances[$supplierId]['details'][] = [
                'purchase_id' => $purchase->id,
                'purchase_no' => $purchase->purchase_no,
                'project_name' => $purchase->project?->name,
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remaining,
                'remaining_by_currency' => $remainingByCurrency,
            ];
        }

        $items = collect($supplierBalances)
            ->sortByDesc('amount')
            ->values()
            ->map(fn (array $row) => [
                ...$row,
                'amount' => round($row['amount'], 2),
                'amount_by_currency' => $row['amount_by_currency'],
            ]);

        return [
            'items' => $items,
            'total' => round($items->sum('amount'), 2),
            'total_by_currency' => self::sumMaps($items->pluck('amount_by_currency')->all()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function inventoryValue(CarbonInterface $asOf, ?int $projectId = null): array
    {
        $query = InventoryTransaction::query()
            ->select(['project_id', 'material_id'])
            ->selectRaw('UPPER(COALESCE(currency, \'USD\')) as currency_code')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as purchased', [InventoryTransaction::TYPE_PURCHASE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as issued', [InventoryTransaction::TYPE_ISSUE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN total_cost_base ELSE 0 END), 0) as purchased_cost_base', [InventoryTransaction::TYPE_PURCHASE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN total_cost ELSE 0 END), 0) as purchased_cost', [InventoryTransaction::TYPE_PURCHASE])
            ->whereDate('transaction_date', '<=', $asOf->toDateString())
            ->groupBy('project_id', 'material_id')
            ->groupByRaw("UPPER(COALESCE(currency, 'USD'))");

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $rows = $query->get();
        $materials = Material::query()->get(['id', 'unit_cost', 'unit_cost_base', 'currency'])->keyBy('id');
        $projects = Project::query()->pluck('name', 'id');
        $materialNames = Material::query()->pluck('name', 'id');

        $items = $rows
            ->map(function ($row) use ($materials, $projects, $materialNames) {
                $onHand = max(0, round((float) $row->purchased - (float) $row->issued, 3));

                if ($onHand <= 0) {
                    return null;
                }

                $material = $materials[$row->material_id] ?? null;
                $currency = CurrencyService::normalize(
                    (string) ($row->currency_code ?: $material?->currency),
                );

                $avgCostBase = (float) $row->purchased > 0
                    ? round((float) $row->purchased_cost_base / (float) $row->purchased, 2)
                    : (float) ($material?->unit_cost_base ?? 0);

                $avgCost = (float) $row->purchased > 0
                    ? round((float) $row->purchased_cost / (float) $row->purchased, 2)
                    : (float) ($material?->unit_cost ?? 0);

                $value = round($onHand * $avgCostBase, 2);
                $valueNative = round($onHand * $avgCost, 2);

                return [
                    'project_id' => $row->project_id,
                    'project_name' => $projects[$row->project_id] ?? null,
                    'material_id' => $row->material_id,
                    'material_name' => $materialNames[$row->material_id] ?? __('Unknown material'),
                    'on_hand' => $onHand,
                    'unit_cost' => $avgCostBase,
                    'value' => $value,
                    'value_by_currency' => self::bucket($currency, $valueNative),
                ];
            })
            ->filter()
            ->sortByDesc('value')
            ->values();

        return [
            'items' => $items,
            'total' => round($items->sum('value'), 2),
            'total_by_currency' => self::sumMaps($items->pluck('value_by_currency')->all()),
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

                $shareByCurrency = self::partnerShareByCurrency($partner, $project->id, $epoch, $asOf);
                $owedByCurrency = self::maxZero(CurrencyAmounts::remaining(
                    $shareByCurrency['entitled_share_by_currency'],
                    $shareByCurrency['paid_total_by_currency'],
                ));

                if ($owed <= 0 && self::isZeroMap($owedByCurrency)) {
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
                    'amount_by_currency' => $owedByCurrency,
                ]);
            }
        }

        return [
            'items' => $items->sortByDesc('amount')->values(),
            'total' => round($items->sum('amount'), 2),
            'total_by_currency' => self::sumMaps($items->pluck('amount_by_currency')->all()),
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
        $investmentsTotalByCurrency = CurrencyAmounts::sum(clone $investmentsQuery);

        $investmentsByPartner = Partner::query()
            ->with(['projects:id,name'])
            ->get()
            ->map(function (Partner $partner) use ($asOf, $projectId) {
                $query = $partner->investments()
                    ->whereDate('invested_at', '<=', $asOf->toDateString())
                    ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

                $total = (float) (clone $query)->sum('amount_base');
                $totalByCurrency = CurrencyAmounts::sum($query->getQuery());

                $byProject = (clone $query)
                    ->select('project_id', DB::raw('SUM(amount_base) as amount'))
                    ->groupBy('project_id')
                    ->get()
                    ->map(function ($row) use ($partner, $asOf, $projectId) {
                        $projectQuery = $partner->investments()
                            ->where('project_id', $row->project_id)
                            ->whereDate('invested_at', '<=', $asOf->toDateString())
                            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

                        return [
                            'project_id' => $row->project_id,
                            'project_name' => Project::query()->whereKey($row->project_id)->value('name'),
                            'amount' => (float) $row->amount,
                            'amount_by_currency' => CurrencyAmounts::sum($projectQuery->getQuery()),
                        ];
                    });

                return [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'amount' => $total,
                    'amount_by_currency' => $totalByCurrency,
                    'by_project' => $byProject->values(),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0 || $row['by_project']->isNotEmpty() || ! self::isZeroMap($row['amount_by_currency']))
            ->sortByDesc('amount')
            ->values();

        $netProfit = self::netProfitToDate($asOf, $projectId);
        $netProfitByCurrency = self::netProfitToDateByCurrency($asOf, $projectId);
        $distributionsPaid = self::sumPartnerDistributions($asOf, $projectId);
        $distributionsPaidByCurrency = self::sumPartnerDistributionsByCurrency($asOf, $projectId);
        $distributionsPayable = self::distributionsPayable($asOf, $projectId);
        $distributionsPayableTotal = $distributionsPayable['total'];
        $distributionsPayableByCurrency = $distributionsPayable['total_by_currency'];
        $retainedEarnings = round($netProfit - $distributionsPaid - $distributionsPayableTotal, 2);
        $retainedEarningsByCurrency = CurrencyAmounts::remaining(
            CurrencyAmounts::remaining($netProfitByCurrency, $distributionsPaidByCurrency),
            $distributionsPayableByCurrency,
        );

        $total = round($investmentsTotal + $retainedEarnings, 2);
        $totalByCurrency = CurrencyAmounts::add($investmentsTotalByCurrency, $retainedEarningsByCurrency);

        return [
            'partner_capital' => [
                'items' => $investmentsByPartner,
                'total' => round($investmentsTotal, 2),
                'total_by_currency' => $investmentsTotalByCurrency,
            ],
            'retained_earnings' => $retainedEarnings,
            'retained_earnings_by_currency' => $retainedEarningsByCurrency,
            'distributions_payable' => $distributionsPayableTotal,
            'distributions_payable_by_currency' => $distributionsPayableByCurrency,
            'total' => $total,
            'total_by_currency' => $totalByCurrency,
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

    /**
     * @return array<string, float>
     */
    public static function netProfitToDateByCurrency(CarbonInterface $asOf, ?int $projectId = null): array
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

        return CurrencyAmounts::net(
            CurrencyAmounts::add(
                Sale::sumTotalPriceByCurrency($salesQuery),
                CurrencyAmounts::sum($incomeQuery),
            ),
            CurrencyAmounts::sum($expenseQuery),
        );
    }

    public static function sumPartnerInvestments(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query = PartnerInvestment::query()
            ->whereDate('invested_at', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

        return (float) $query->sum('amount_base');
    }

    /**
     * @return array<string, float>
     */
    public static function sumPartnerInvestmentsByCurrency(CarbonInterface $asOf, ?int $projectId = null): array
    {
        return CurrencyAmounts::sum(
            PartnerInvestment::query()
                ->whereDate('invested_at', '<=', $asOf->toDateString())
                ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId)),
        );
    }

    public static function sumPartnerDistributions(CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query = PartnerDistribution::query()
            ->whereDate('distributed_at', '<=', $asOf->toDateString())
            ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));

        return (float) $query->sum('amount_base');
    }

    /**
     * @return array<string, float>
     */
    public static function sumPartnerDistributionsByCurrency(CarbonInterface $asOf, ?int $projectId = null): array
    {
        return CurrencyAmounts::sum(
            PartnerDistribution::query()
                ->whereDate('distributed_at', '<=', $asOf->toDateString())
                ->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId)),
        );
    }

    private static function epoch(CarbonInterface $asOf): CarbonInterface
    {
        return Carbon::create(self::EPOCH_START_YEAR, 1, 1, 0, 0, 0, $asOf->getTimezone());
    }

    /**
     * @return array{entitled_share_by_currency: array<string, float>, paid_total_by_currency: array<string, float>}
     */
    private static function partnerShareByCurrency(
        Partner $partner,
        int $projectId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $ownership = $partner->ownershipOnProject($projectId) / 100;
        $netProfit = self::netProfitToDateByCurrency($to, $projectId);

        // Restrict to the same window ShareholderProfitService uses when from/to are set.
        // Balance sheet always uses epoch→asOf, and netProfitToDateByCurrency is already to-date.
        $entitled = [];
        foreach (['AFN', 'USD'] as $code) {
            $entitled[$code] = round(($netProfit[$code] ?? 0) * $ownership, 2);
        }
        foreach ($netProfit as $code => $amount) {
            $code = CurrencyService::normalize((string) $code);
            if (! isset($entitled[$code])) {
                $entitled[$code] = round((float) $amount * $ownership, 2);
            }
        }
        ksort($entitled);

        $paidQuery = $partner->distributions()
            ->where('project_id', $projectId)
            ->whereDate('distributed_at', '>=', $from->toDateString())
            ->whereDate('distributed_at', '<=', $to->toDateString());

        return [
            'entitled_share_by_currency' => $entitled,
            'paid_total_by_currency' => CurrencyAmounts::sum($paidQuery->getQuery()),
        ];
    }

    private static function sumPaymentQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->whereNotNull('paid_at')->where('paid_at', '<=', $asOf);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }

    /**
     * @return array<string, float>
     */
    private static function sumPaymentQueryByCurrency(Builder $query, CarbonInterface $asOf, ?int $projectId = null): array
    {
        $query->whereNotNull('paid_at')->where('paid_at', '<=', $asOf);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return CurrencyAmounts::sum($query);
    }

    private static function sumIncomeQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->where('status', 'approved')->whereDate('income_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }

    /**
     * @return array<string, float>
     */
    private static function sumIncomeQueryByCurrency(Builder $query, CarbonInterface $asOf, ?int $projectId = null): array
    {
        $query->where('status', 'approved')->whereDate('income_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return CurrencyAmounts::sum($query);
    }

    private static function sumVoucherQuery(Builder $query, CarbonInterface $asOf, ?int $projectId = null): float
    {
        $query->whereDate('voucher_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount_base');
    }

    /**
     * @return array<string, float>
     */
    private static function sumVoucherQueryByCurrency(Builder $query, CarbonInterface $asOf, ?int $projectId = null): array
    {
        $query->whereDate('voucher_date', '<=', $asOf->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return CurrencyAmounts::sum($query);
    }

    /**
     * @return array<string, float>
     */
    private static function empty(): array
    {
        return ['AFN' => 0.0, 'USD' => 0.0];
    }

    /**
     * @return array<string, float>
     */
    private static function bucket(?string $currency, float $amount): array
    {
        $totals = self::empty();
        $code = CurrencyService::normalize($currency);
        $totals[$code] = round($amount, 2);

        return $totals;
    }

    /**
     * @param  array<string, float>  $amounts
     * @return array<string, float>
     */
    private static function maxZero(array $amounts): array
    {
        $out = self::empty();

        foreach ($amounts as $code => $amount) {
            $code = CurrencyService::normalize((string) $code);
            $out[$code] = max(0, round((float) $amount, 2));
        }

        ksort($out);

        return $out;
    }

    /**
     * @param  array<string, float>  $amounts
     */
    private static function isZeroMap(array $amounts): bool
    {
        foreach ($amounts as $amount) {
            if (abs((float) $amount) >= 0.01) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, float>|null>  $maps
     * @return array<string, float>
     */
    private static function sumMaps(array $maps): array
    {
        return CurrencyAmounts::add(self::empty(), ...array_values(array_filter($maps)));
    }
}
