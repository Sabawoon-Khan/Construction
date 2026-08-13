<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
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
        return [
            'project_id' => $projectId,
            'expense_category_id' => FinanceDefaults::generalExpenseCategoryId(),
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'expense_no' => 'EXP-'.Str::upper(Str::random(8)),
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
        ];
    }

    /** @return array<string, mixed> */
    public static function incomeAttributes(array $data): array
    {
        return [
            'project_id' => null,
            'income_category_id' => FinanceDefaults::generalIncomeCategoryId(),
            'income_date' => $data['income_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'income_no' => 'INC-'.Str::upper(Str::random(8)),
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
        ];
    }
}
