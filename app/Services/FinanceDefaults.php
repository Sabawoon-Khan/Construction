<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class FinanceDefaults
{
    public static function generalExpenseCategoryId(): int
    {
        $category = ExpenseCategory::query()
            ->where('code', 'general')
            ->orWhere('name', 'General')
            ->first();

        if ($category) {
            return $category->id;
        }

        return ExpenseCategory::query()->create([
            'name' => 'General',
            'code' => 'general',
            'description' => 'Default category for simplified expenses.',
            'is_active' => true,
        ])->id;
    }

    public static function generalIncomeCategoryId(): int
    {
        $category = IncomeCategory::query()
            ->where('code', 'general')
            ->orWhere('name', 'General')
            ->first();

        if ($category) {
            return $category->id;
        }

        return IncomeCategory::query()->create([
            'name' => 'General',
            'code' => 'general',
            'description' => 'Default category for simplified incomes.',
            'is_active' => true,
        ])->id;
    }

    /** @return array<string, mixed> */
    public static function expenseAttributes(array $data, ?int $projectId = null): array
    {
        ExpenseCategory::ensureDefaults();

        $currency = CurrencyService::normalize($data['currency'] ?? null, self::baseCurrency());
        $date = isset($data['expense_date']) ? Carbon::parse($data['expense_date']) : now();
        $exchangeRate = self::exchangeRate($currency, $date);
        $itemAmount = isset($data['item_amount']) ? (float) $data['item_amount'] : null;
        $perPrice = isset($data['per_price']) ? round((float) $data['per_price'], 2) : null;
        $amount = isset($data['amount'])
            ? round((float) $data['amount'], 2)
            : self::totalCost($data);

        // Some flows (e.g. project expense quick add) provide only `amount`.
        // Ensure item/per fields are always populated for NOT NULL DB columns.
        if (($itemAmount === null || $itemAmount <= 0) && $amount >= 0) {
            $itemAmount = 1.0;
        }

        if ($perPrice === null) {
            $perPrice = $itemAmount > 0 ? round($amount / $itemAmount, 2) : $amount;
        }

        return [
            'project_id' => $projectId,
            'expense_category_id' => ! empty($data['expense_category_id'])
                ? $data['expense_category_id']
                : FinanceDefaults::generalExpenseCategoryId(),
            'expense_date' => $data['expense_date'],
            'paid_to' => $data['paid_to'] ?? null,
            'expense_unit_id' => $data['expense_unit_id'] ?? null,
            'item_amount' => $itemAmount,
            'item_amount_base' => $itemAmount,
            'per_price' => $perPrice,
            'per_price_base' => round($perPrice * $exchangeRate, 2),
            'amount' => $amount,
            'amount_base' => round($amount * $exchangeRate, 2),
            'description' => $data['description'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'expense_no' => 'EXP-'.Str::upper(Str::random(8)),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'status' => 'approved',
            'purchase_id' => $data['purchase_id'] ?? null,
        ];
    }

    public static function totalCost(array $data): float
    {
        return round(((float) ($data['item_amount'] ?? 0)) * ((float) ($data['per_price'] ?? 0)), 2);
    }

    /** @return array<string, mixed> */
    public static function incomeAttributes(array $data): array
    {
        $currency = CurrencyService::normalize($data['currency'] ?? null, self::baseCurrency());
        $date = isset($data['income_date']) ? Carbon::parse($data['income_date']) : now();
        $exchangeRate = self::exchangeRate($currency, $date);
        $amount = round((float) $data['amount'], 2);

        return [
            'project_id' => null,
            'income_category_id' => FinanceDefaults::generalIncomeCategoryId(),
            'income_date' => $data['income_date'],
            'amount' => $amount,
            'amount_base' => round($amount * $exchangeRate, 2),
            'description' => $data['description'] ?? null,
            'income_no' => 'INC-'.Str::upper(Str::random(8)),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'status' => 'approved',
        ];
    }

    public static function baseCurrency(): string
    {
        return CurrencyService::baseCurrency();
    }

    public static function exchangeRate(string $currency, ?CarbonInterface $date = null): float
    {
        $date ??= now();
        $baseCurrency = self::baseCurrency();
        $currency = CurrencyService::normalize($currency, $baseCurrency);

        if ($currency === $baseCurrency) {
            return 1.0;
        }

        return CurrencyService::convert(1.0, $currency, $baseCurrency, $date) ?? 1.0;
    }

    public static function convertToBase(float $amount, string $currency, ?CarbonInterface $date = null): float
    {
        return round($amount * self::exchangeRate($currency, $date), 2);
    }
}
