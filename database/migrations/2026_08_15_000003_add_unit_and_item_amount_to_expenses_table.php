<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['supplier_id', 'contractor_id', 'bank_account_id'] as $column) {
            if (Schema::hasColumn('expenses', $column)) {
                Schema::table('expenses', function (Blueprint $table) use ($column) {
                    $table->dropConstrainedForeignId($column);
                });
            }
        }

        if (! Schema::hasColumn('expenses', 'expense_unit_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreignId('expense_unit_id')->nullable()->after('paid_to')->constrained('expense_units')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('expenses', 'item_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('item_amount', 18, 2)->nullable()->after('expense_unit_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'expense_unit_id')) {
                $table->dropConstrainedForeignId('expense_unit_id');
            }

            if (Schema::hasColumn('expenses', 'item_amount')) {
                $table->dropColumn('item_amount');
            }
        });
    }
};
