<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    /**
     * @param  array{
     *     project_id: int,
     *     supplier_id: int,
     *     purchase_date: string,
     *     reference_no?: string|null,
     *     notes?: string|null,
     *     currency?: string,
     *     paid_now?: float|int|string,
     *     payment_method?: string|null,
     *     items: list<array{material_id: int, quantity: float|int|string, unit_cost: float|int|string}>
     * }  $data
     */
    public static function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
            $purchaseDate = Carbon::parse($data['purchase_date']);
            $exchangeRate = FinanceDefaults::exchangeRate($currency, $purchaseDate);

            $items = collect($data['items'] ?? [])
                ->map(function (array $row) use ($exchangeRate) {
                    $quantity = round((float) $row['quantity'], 3);
                    $unitCost = round((float) $row['unit_cost'], 2);
                    $totalCost = round($quantity * $unitCost, 2);

                    return [
                        'material_id' => (int) $row['material_id'],
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'unit_cost_base' => round($unitCost * $exchangeRate, 2),
                        'total_cost' => $totalCost,
                        'total_cost_base' => round($totalCost * $exchangeRate, 2),
                    ];
                })
                ->filter(fn(array $row) => $row['quantity'] > 0)
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one material line.',
                ]);
            }

            $total = round((float) $items->sum('total_cost'), 2);
            $totalBase = round((float) $items->sum('total_cost_base'), 2);
            $paidNow = round((float) ($data['paid_now'] ?? 0), 2);
            $paidNowBase = round($paidNow * $exchangeRate, 2);

            if ($paidNow < 0 || $paidNow > $total) {
                throw ValidationException::withMessages([
                    'paid_now' => 'Paid now must be between 0 and the purchase total.',
                ]);
            }

            $supplier = Supplier::query()->findOrFail($data['supplier_id']);

            $purchase = Purchase::query()->create([
                'project_id' => $data['project_id'],
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-' . Str::upper(Str::random(8)),
                'purchase_date' => $data['purchase_date'],
                'total_amount' => $total,
                'total_amount_base' => $totalBase,
                'paid_amount' => 0,
                'paid_amount_base' => 0,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'status' => 'unpaid',
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $row) {
                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    ...$row,
                ]);
            }

            $description = self::expenseDescription($purchase->fresh(['items.material']));

            $expense = Expense::query()->create(FinanceDefaults::expenseAttributes([
                'expense_date' => $data['purchase_date'],
                'paid_to' => $supplier->name,
                'amount' => $total,
                'item_amount' => 1,
                'per_price' => $total,
                'description' => $description,
                'reference_no' => $data['reference_no'] ?? $purchase->purchase_no,
                'expense_category_id' => self::materialsCategoryId(),
                'purchase_id' => $purchase->id,
                'currency' => $currency,
            ], (int) $purchase->project_id));

            $expense->forceFill([
                'purchase_id' => $purchase->id,
                'amount' => $total,
                'amount_base' => $totalBase,
                'per_price' => $total,
                'per_price_base' => $totalBase,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
            ])->save();

            foreach ($purchase->items()->get() as $item) {
                InventoryTransaction::query()->create([
                    'project_id' => $purchase->project_id,
                    'material_id' => $item->material_id,
                    'supplier_id' => $purchase->supplier_id,
                    'expense_id' => $expense->id,
                    'purchase_id' => $purchase->id,
                    'type' => InventoryTransaction::TYPE_PURCHASE,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'unit_cost_base' => $item->unit_cost_base,
                    'total_cost' => $item->total_cost,
                    'total_cost_base' => $item->total_cost_base,
                    'currency' => $purchase->currency,
                    'exchange_rate' => $purchase->exchange_rate,
                    'transaction_date' => $purchase->purchase_date,
                    'reference_no' => $purchase->purchase_no,
                    'notes' => null,
                ]);
            }

            if ($paidNow > 0) {
                self::recordPayment($purchase->fresh(), $paidNow, [
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'paid_at' => $purchase->purchase_date,
                ]);
            } else {
                self::syncStatus($purchase);
            }

            return $purchase->fresh(['items', 'supplier', 'project', 'expense']) ?? $purchase;
        });
    }

    /**
     * @param  array{payment_method?: string, reference_no?: string|null, notes?: string|null, paid_at?: mixed}  $meta
     */
    public static function recordPayment(Purchase $purchase, float $amount, array $meta = []): void
    {
        DB::transaction(function () use ($purchase, $amount, $meta) {
            $purchase->refresh();
            $remaining = $purchase->remaining_amount;
            $amount = round($amount, 2);
            $exchangeRate = (float) $purchase->exchange_rate;
            $amountBase = round($amount * $exchangeRate, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the remaining balance of ' . $remaining . '.',
                ]);
            }

            Payment::query()->create([
                'project_id' => $purchase->project_id,
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'bank_account_id' => null,
                'payment_no' => 'PAY-' . Str::upper(Str::random(8)),
                'type' => 'disbursement',
                'amount' => $amount,
                'amount_base' => $amountBase,
                'currency' => $purchase->currency,
                'exchange_rate' => $exchangeRate,
                'paid_at' => $meta['paid_at'] ?? now(),
                'payment_method' => $meta['payment_method'] ?? 'cash',
                'reference_no' => $meta['reference_no'] ?? null,
                'status' => 'posted',
                'notes' => $meta['notes'] ?? null,
            ]);

            $purchase->paid_amount = round((float) $purchase->paid_amount + $amount, 2);
            $purchase->paid_amount_base = round((float) $purchase->paid_amount_base + $amountBase, 2);
            self::syncStatus($purchase);
        });
    }

    /**
     * @param  array{transaction_date?: mixed, reference_no?: string|null, notes?: string|null}  $meta
     */
    public static function issue(int $projectId, int $materialId, float $quantity, array $meta = []): InventoryTransaction
    {
        return DB::transaction(function () use ($projectId, $materialId, $quantity, $meta) {
            $quantity = round($quantity, 3);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Issue quantity must be greater than zero.',
                ]);
            }

            $onHand = InventoryTransaction::onHand($projectId, $materialId);

            if ($quantity > $onHand) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot issue more than the ' . $onHand . ' currently in stock for this project.',
                ]);
            }

            $unitCostBase = self::averageUnitCostBase($projectId, $materialId);
            $totalCostBase = round($quantity * $unitCostBase, 2);
            $baseCurrency = CurrencyService::baseCurrency();

            return InventoryTransaction::query()->create([
                'project_id' => $projectId,
                'material_id' => $materialId,
                'supplier_id' => null,
                'expense_id' => null,
                'purchase_id' => null,
                'type' => InventoryTransaction::TYPE_ISSUE,
                'quantity' => $quantity,
                'unit_cost' => $unitCostBase,
                'unit_cost_base' => $unitCostBase,
                'total_cost' => $totalCostBase,
                'total_cost_base' => $totalCostBase,
                'currency' => $baseCurrency,
                'exchange_rate' => 1,
                'transaction_date' => $meta['transaction_date'] ?? now(),
                'reference_no' => $meta['reference_no'] ?? null,
                'notes' => $meta['notes'] ?? null,
            ]);
        });
    }

    /**
     * Transfer stock from one project to another.
     * Returns an array with two InventoryTransaction instances: [issueFrom, purchaseTo]
     *
     * @param array{transaction_date?: mixed, reference_no?: string|null, notes?: string|null} $meta
     * @return InventoryTransaction[]
     */
    public static function transfer(int $fromProjectId, int $toProjectId, int $materialId, float $quantity, array $meta = []): array
    {
        return DB::transaction(function () use ($fromProjectId, $toProjectId, $materialId, $quantity, $meta) {
            $quantity = round($quantity, 3);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Transfer quantity must be greater than zero.',
                ]);
            }

            $onHand = InventoryTransaction::onHand($fromProjectId, $materialId);

            if ($quantity > $onHand) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot transfer more than the ' . $onHand . ' currently in stock for the source project.',
                ]);
            }

            // Determine unit cost from source project's average
            $unitCostBase = self::averageUnitCostBase($fromProjectId, $materialId);
            $totalCostBase = round($quantity * $unitCostBase, 2);
            $baseCurrency = CurrencyService::baseCurrency();


            // Create a transfer out transaction on the source project
            $issue = InventoryTransaction::query()->create([
                'project_id' => $fromProjectId,
                'material_id' => $materialId,
                'supplier_id' => null,
                'expense_id' => null,
                'purchase_id' => null,
                'type' => InventoryTransaction::TYPE_TRANSFER_OUT,
                'quantity' => $quantity,
                'unit_cost' => $unitCostBase,
                'unit_cost_base' => $unitCostBase,
                'total_cost' => $totalCostBase,
                'total_cost_base' => $totalCostBase,
                'currency' => $baseCurrency,
                'exchange_rate' => 1,
                'transaction_date' => $meta['transaction_date'] ?? now(),
                'reference_no' => $meta['reference_no'] ?? null,
                'notes' => $meta['notes'] ?? 'Transfer out to project '.$toProjectId,
                'to_project_id' => $toProjectId,
            ]);

            // Create a transfer in transaction on the destination project
            $purchase = InventoryTransaction::query()->create([
                'project_id' => $toProjectId,
                'material_id' => $materialId,
                'supplier_id' => null,
                'expense_id' => null,
                'purchase_id' => null,
                'type' => InventoryTransaction::TYPE_TRANSFER_IN,
                'quantity' => $quantity,
                'unit_cost' => $unitCostBase,
                'unit_cost_base' => $unitCostBase,
                'total_cost' => $totalCostBase,
                'total_cost_base' => $totalCostBase,
                'currency' => $baseCurrency,
                'exchange_rate' => 1,
                'transaction_date' => $meta['transaction_date'] ?? now(),
                'reference_no' => $meta['reference_no'] ?? null,
                'notes' => $meta['notes'] ?? 'Transfer in from project '.$fromProjectId,
                'from_project_id' => $fromProjectId,
            ]);

            return [$issue, $purchase];
        });
    }

    /**
     * @param  array{quantity: float|int|string, transaction_date?: mixed, reference_no?: string|null, notes?: string|null}  $data
     */
    public static function updateIssue(InventoryTransaction $transaction, array $data): InventoryTransaction
    {
        if ($transaction->type !== InventoryTransaction::TYPE_ISSUE) {
            throw ValidationException::withMessages([
                'inventory' => 'Only stock issues can be updated.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $data) {
            $quantity = round((float) $data['quantity'], 3);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Issue quantity must be greater than zero.',
                ]);
            }

            $onHand = InventoryTransaction::onHand((int) $transaction->project_id, (int) $transaction->material_id);
            $maxQuantity = $onHand + (float) $transaction->quantity;

            if ($quantity > $maxQuantity + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot issue more than the ' . $maxQuantity . ' currently available for this project.',
                ]);
            }

            $unitCostBase = self::averageUnitCostBase((int) $transaction->project_id, (int) $transaction->material_id);
            $totalCostBase = round($quantity * $unitCostBase, 2);

            $transaction->update([
                'quantity' => $quantity,
                'unit_cost' => $unitCostBase,
                'unit_cost_base' => $unitCostBase,
                'total_cost' => $totalCostBase,
                'total_cost_base' => $totalCostBase,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
                'reference_no' => array_key_exists('reference_no', $data)
                    ? $data['reference_no']
                    : $transaction->reference_no,
                'notes' => array_key_exists('notes', $data)
                    ? $data['notes']
                    : $transaction->notes,
            ]);

            return $transaction->fresh();
        });
    }

    public static function deleteIssue(InventoryTransaction $transaction): void
    {
        if ($transaction->type !== InventoryTransaction::TYPE_ISSUE) {
            throw ValidationException::withMessages([
                'inventory' => 'Only stock issues can be deleted.',
            ]);
        }

        $transaction->delete();
    }

    public static function assertDeletable(Purchase $purchase): void
    {
        $purchase->loadMissing('items');

        foreach ($purchase->items as $item) {
            $onHand = InventoryTransaction::onHand((int) $purchase->project_id, (int) $item->material_id);

            if ($onHand + 0.0001 < (float) $item->quantity) {
                throw ValidationException::withMessages([
                    'purchase' => 'This purchase cannot be deleted because some of its stock has already been issued.',
                ]);
            }
        }
    }

    public static function syncStatus(Purchase $purchase): void
    {
        $purchase->status = Purchase::statusFor((float) $purchase->total_amount, (float) $purchase->paid_amount);
        $purchase->save();
    }

    public static function materialsCategoryId(): int
    {
        ExpenseCategory::ensureDefaults();

        $category = ExpenseCategory::query()
            ->where('code', 'materials')
            ->orWhere('name', 'Materials')
            ->first();

        if ($category) {
            return $category->id;
        }

        return FinanceDefaults::generalExpenseCategoryId();
    }

    private static function expenseDescription(Purchase $purchase): string
    {
        $names = $purchase->items
            ->map(fn(PurchaseItem $item) => $item->material?->name)
            ->filter()
            ->unique()
            ->values();

        $list = $names->isNotEmpty() ? $names->implode(', ') : 'materials';

        return 'Purchase ' . $purchase->purchase_no . ': ' . $list;
    }

    private static function averageUnitCostBase(int $projectId, int $materialId): float
    {
        $purchased = InventoryTransaction::query()
            ->where('project_id', $projectId)
            ->where('material_id', $materialId)
            ->where('type', InventoryTransaction::TYPE_PURCHASE)
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(total_cost_base), 0) as cost')
            ->first();

        $qty = (float) ($purchased?->qty ?? 0);

        if ($qty <= 0) {
            return 0;
        }

        return round((float) ($purchased?->cost ?? 0) / $qty, 2);
    }
}
