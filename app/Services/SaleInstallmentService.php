<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleInstallment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleInstallmentService
{
    /**
     * @param  array{payment_method?: string, bank_account_id?: int|null, reference_no?: string|null, notes?: string|null, paid_at?: mixed}  $meta
     */
    public static function recordPayment(Sale $sale, SaleInstallment $startInstallment, float $amount, array $meta = []): void
    {
        DB::transaction(function () use ($sale, $startInstallment, $amount, $meta) {
            $sale->loadMissing('unit');
            $installments = $sale->installments()->orderBy('installment_no')->get();
            $currency = strtoupper($sale->currency ?? CurrencyService::baseCurrency());
            $paidAt = isset($meta['paid_at']) ? Carbon::parse($meta['paid_at']) : now();
            $exchangeRate = FinanceDefaults::exchangeRate($currency, $paidAt);

            self::allocateAmount($installments, $amount, $startInstallment->id);

            foreach ($installments as $installment) {
                $paid = (float) $installment->paid_amount;
                $due = (float) $installment->amount;

                $installment->status = $paid >= $due
                    ? 'paid'
                    : ($paid > 0 ? 'partial' : 'pending');
                $installment->save();
            }

            $amountBase = round($amount * $exchangeRate, 2);

            Payment::create([
                'project_id' => $sale->project_id,
                'sale_id' => $sale->id,
                'sale_installment_id' => $startInstallment->id,
                'customer_id' => $sale->customer_id,
                'bank_account_id' => $meta['bank_account_id'] ?? null,
                'payment_no' => 'PAY-'.Str::upper(Str::random(8)),
                'type' => 'receipt',
                'amount' => $amount,
                'amount_base' => $amountBase,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'paid_at' => $meta['paid_at'] ?? now(),
                'payment_method' => $meta['payment_method'] ?? 'cash',
                'reference_no' => $meta['reference_no'] ?? null,
                'status' => 'posted',
                'notes' => $meta['notes'] ?? null,
            ]);

            self::syncSaleStatus($sale);
        });
    }

    public static function syncSaleStatus(Sale $sale): void
    {
        $sale->loadMissing('unit');
        $paidTotal = (float) $sale->installments()->sum('paid_amount');
        $netPrice = (float) $sale->total_price;

        if ($netPrice > 0 && $paidTotal >= $netPrice && $sale->status === 'active') {
            $sale->update(['status' => 'completed']);
        } elseif ($paidTotal < $netPrice && $sale->status === 'completed') {
            $sale->update(['status' => 'active']);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SaleInstallment>  $installments
     */
    private static function allocateAmount($installments, float $amount, ?int $startInstallmentId = null): void
    {
        if ($amount <= 0 || $installments->isEmpty()) {
            return;
        }

        $remaining = $amount;
        $startIndex = 0;
        $exchangeRate = $installments->first()->exchange_rate ?? 1;

        if ($startInstallmentId) {
            $index = $installments->search(fn ($i) => $i->id === $startInstallmentId);
            if ($index !== false) {
                $startIndex = $index;
            }
        }

        for ($idx = $startIndex; $idx < $installments->count() && $remaining > 0; $idx++) {
            $installment = $installments[$idx];
            $due = max(0, (float) $installment->amount - (float) $installment->paid_amount);

            if ($due <= 0) {
                continue;
            }

            $apply = min($remaining, $due);
            $installment->paid_amount = (float) $installment->paid_amount + $apply;
            $installment->paid_amount_base = round((float) $installment->paid_amount_base + ($apply * $exchangeRate), 2);
            $remaining -= $apply;
        }

        if ($remaining > 0) {
            $last = $installments->last();
            $last->paid_amount = (float) $last->paid_amount + $remaining;
            $last->paid_amount_base = round((float) $last->paid_amount_base + ($remaining * $exchangeRate), 2);
        }
    }
}
