<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $baseCurrency = strtoupper(Company::query()->value('base_currency') ?? 'USD');

        $this->backfillTable('payments', ['amount'], 'currency');
        $this->backfillTable('purchases', ['total_amount', 'paid_amount'], 'currency');
        $this->backfillTable('purchase_items', ['unit_cost', 'total_cost']);
        $this->backfillTable('expenses', ['amount', 'item_amount', 'per_price'], 'currency');
        $this->backfillTable('incomes', ['amount'], 'currency');
        $this->backfillTable('vouchers', ['amount'], 'currency');
        $this->backfillTable('financial_transactions', ['amount'], 'currency');
        $this->backfillTable('bank_accounts', ['opening_balance'], 'currency');
        $this->backfillTable('units', ['cost_price', 'list_price'], 'currency');
        $this->backfillTable('materials', ['unit_cost'], 'currency');
        $this->backfillTable('inventory_transactions', ['unit_cost', 'total_cost'], 'currency');
        $this->backfillTable('sale_installments', ['amount', 'paid_amount'], 'currency');
        $this->backfillTable('partner_investments', ['amount'], 'currency');
        $this->backfillTable('partner_distributions', ['amount'], 'currency');
        $this->backfillTable('employees', ['basic_salary'], 'currency');
        $this->backfillTable('salaries', ['basic_salary', 'bonus', 'deduction', 'net_salary'], 'currency');

        // For rows in the base currency, the conversion is exact.
        // For rows in other currencies, a 1:1 fallback is applied so existing
        // reports continue to work. These rows should be reviewed and the correct
        // exchange rate should be entered via Settings > Company > Exchange rates.
        DB::table('exchange_rates')->insertOrIgnore([
            'from_currency' => $baseCurrency,
            'to_currency' => $baseCurrency,
            'rate' => 1,
            'effective_date' => '1900-01-01',
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No-op: backfill is not reversible without losing data.
    }

    /**
     * @param  string[]  $columns
     */
    private function backfillTable(string $table, array $columns, ?string $currencyColumn = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $set = [];
        foreach ($columns as $column) {
            $baseColumn = $column.'_base';
            if (Schema::hasColumn($table, $baseColumn)) {
                $set[$baseColumn] = DB::raw($column);
            }
        }

        if ($currencyColumn !== null && Schema::hasColumn($table, $currencyColumn)) {
            $set['exchange_rate'] = 1;
        }

        if ($set === []) {
            return;
        }

        DB::table($table)->update($set);
    }
};
