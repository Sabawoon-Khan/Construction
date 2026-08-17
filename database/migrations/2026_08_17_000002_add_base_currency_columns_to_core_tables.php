<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_amount_base', 18, 2)->default(0)->after('total_amount');
            $table->decimal('paid_amount_base', 18, 2)->default(0)->after('paid_amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('unit_cost_base', 18, 2)->default(0)->after('unit_cost');
            $table->decimal('total_cost_base', 18, 2)->default(0)->after('total_cost');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('item_amount_base', 18, 2)->default(0)->after('item_amount');
            $table->decimal('per_price_base', 18, 2)->default(0)->after('per_price');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['total_amount_base', 'paid_amount_base', 'exchange_rate']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_base', 'total_cost_base']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'item_amount_base', 'per_price_base', 'exchange_rate']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });
    }
};
