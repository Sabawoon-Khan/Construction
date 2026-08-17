<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->decimal('opening_balance_base', 18, 2)->default(0)->after('opening_balance');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->decimal('cost_price_base', 18, 2)->default(0)->after('cost_price');
            $table->decimal('list_price_base', 18, 2)->default(0)->after('list_price');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('unit_cost_base', 18, 2)->default(0)->after('unit_cost');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('unit_cost_base', 18, 2)->default(0)->after('unit_cost');
            $table->decimal('total_cost_base', 18, 2)->default(0)->after('total_cost');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('sale_installments', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('paid_amount');
            $table->decimal('amount_base', 18, 2)->default(0)->after('currency');
            $table->decimal('paid_amount_base', 18, 2)->default(0)->after('amount_base');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('paid_amount_base');
        });

        Schema::table('partner_investments', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('partner_distributions', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 2)->default(0)->after('amount');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary_base', 18, 2)->default(0)->after('basic_salary');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('net_salary');
            $table->decimal('basic_salary_base', 18, 2)->default(0)->after('currency');
            $table->decimal('bonus_base', 18, 2)->default(0)->after('bonus');
            $table->decimal('deduction_base', 18, 2)->default(0)->after('deduction');
            $table->decimal('net_salary_base', 18, 2)->default(0)->after('net_salary');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('net_salary_base');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['opening_balance_base', 'exchange_rate']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['cost_price_base', 'list_price_base', 'exchange_rate']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_base', 'exchange_rate']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_base', 'total_cost_base', 'exchange_rate']);
        });

        Schema::table('sale_installments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_base', 'paid_amount_base', 'exchange_rate']);
        });

        Schema::table('partner_investments', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });

        Schema::table('partner_distributions', function (Blueprint $table) {
            $table->dropColumn(['amount_base', 'exchange_rate']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['basic_salary_base', 'exchange_rate']);
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['currency', 'basic_salary_base', 'bonus_base', 'deduction_base', 'net_salary_base', 'exchange_rate']);
        });
    }
};
