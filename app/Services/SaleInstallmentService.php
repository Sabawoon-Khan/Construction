<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleInstallmentService
{
    public static function recordPayment(Sale $sale, SaleInstallment $startInstallment, float $amount): void
    {
        DB::transaction(function () use ($sale, $startInstallment, $amount) {
            $sale->loadMissing('unit');
            $installments = $sale->installments()->orderBy('installment_no')->get();

            self::allocateAmount($installments, $amount, $startInstallment->id);

            foreach ($installments as $installment) {
                $paid = (float) $installment->paid_amount;
                $due = (float) $installment->amount;

                $installment->status = $paid >= $due
                    ? 'paid'
                    : ($paid > 0 ? 'partial' : 'pending');
                $installment->save();
            }

            Payment::create([
                'project_id' => $sale->project_id,
                'sale_id' => $sale->id,
                'sale_installment_id' => $startInstallment->id,
                'customer_id' => $sale->customer_id,
                'payment_no' => 'PAY-'.Str::upper(Str::random(8)),
                'type' => 'receipt',
                'amount' => $amount,
                'currency' => $sale->currency ?? 'USD',
                'paid_at' => now(),
                'payment_method' => 'cash',
                'status' => 'posted',
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
            $remaining -= $apply;
        }

        if ($remaining > 0) {
            $installments->last()->paid_amount = (float) $installments->last()->paid_amount + $remaining;
        }
    }
}
