<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that gain a nullable user_id (who created the row).
     *
     * @var list<string>
     */
    private array $tables = [
        'projects',
        'units',
        'customers',
        'suppliers',
        'materials',
        'material_units',
        'purchases',
        'purchase_items',
        'sales',
        'sale_installments',
        'expenses',
        'incomes',
        'payments',
        'employees',
        'attendance',
        'salaries',
        'expense_units',
        'inventory_transactions',
        'financial_transactions',
        'bank_accounts',
        'expense_categories',
        'income_categories',
        'partner_investments',
        'partner_distributions',
        'lookup_items',
        'companies',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('partners') && ! Schema::hasColumn('partners', 'created_by')) {
            Schema::table('partners', function (Blueprint $blueprint) {
                $blueprint->foreignId('created_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasTable('partners') && Schema::hasColumn('partners', 'created_by')) {
            Schema::table('partners', function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('created_by');
            });
        }
    }
};
