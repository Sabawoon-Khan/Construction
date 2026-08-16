<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('purchase_no')->unique();
            $table->date('purchase_date');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('unpaid');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('sale_installment_id')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('expense_id')->constrained()->nullOnDelete();
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
            $table->dropConstrainedForeignId('supplier_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
        });

        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');

        Schema::table('materials', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
