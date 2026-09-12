<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanService
{
    /**
     * @param  array{
     *     lender_name: string,
     *     loan_date: mixed,
     *     due_date?: mixed,
     *     principal: float|int|string,
     *     currency?: string|null,
     *     reference_no?: string|null,
     *     notes?: string|null,
     * }  $data
     */
    public static function create(int $projectId, array $data): Loan
    {
        return DB::transaction(function () use ($projectId, $data) {
            $loanDate = Carbon::parse($data['loan_date']);
            $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
            $exchangeRate = FinanceDefaults::exchangeRate($currency, $loanDate);
            $principal = round((float) $data['principal'], 2);

            if ($principal <= 0) {
                throw ValidationException::withMessages([
                    'principal' => 'Loan amount must be greater than zero.',
                ]);
            }

            return Loan::query()->create([
                'project_id' => $projectId,
                'loan_no' => 'LN-'.Str::upper(Str::random(8)),
                'lender_name' => $data['lender_name'],
                'loan_date' => $loanDate->toDateString(),
                'due_date' => ! empty($data['due_date']) ? Carbon::parse($data['due_date'])->toDateString() : null,
                'principal' => $principal,
                'principal_base' => round($principal * $exchangeRate, 2),
                'paid_amount' => 0,
                'paid_amount_base' => 0,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'status' => 'unpaid',
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{payment_method?: string, reference_no?: string|null, notes?: string|null, paid_at?: mixed}  $meta
     */
    public static function recordPayment(Loan $loan, float $amount, array $meta = []): void
    {
        DB::transaction(function () use ($loan, $amount, $meta) {
            $loan->refresh();
            $remaining = $loan->remaining_amount;
            $amount = round($amount, 2);
            $exchangeRate = (float) $loan->exchange_rate;
            $amountBase = round($amount * $exchangeRate, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the remaining balance of '.$remaining.'.',
                ]);
            }

            Payment::query()->create([
                'project_id' => $loan->project_id,
                'loan_id' => $loan->id,
                'bank_account_id' => null,
                'payment_no' => 'PAY-'.Str::upper(Str::random(8)),
                'type' => 'disbursement',
                'amount' => $amount,
                'amount_base' => $amountBase,
                'currency' => $loan->currency,
                'exchange_rate' => $exchangeRate,
                'paid_at' => $meta['paid_at'] ?? now(),
                'payment_method' => $meta['payment_method'] ?? 'cash',
                'reference_no' => $meta['reference_no'] ?? null,
                'status' => 'posted',
                'notes' => $meta['notes'] ?? null,
            ]);

            $loan->paid_amount = round((float) $loan->paid_amount + $amount, 2);
            $loan->paid_amount_base = round((float) $loan->paid_amount_base + $amountBase, 2);
            $loan->status = Loan::statusFor((float) $loan->principal, (float) $loan->paid_amount);
            $loan->save();
        });
    }
}
