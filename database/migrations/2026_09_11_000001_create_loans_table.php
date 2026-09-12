<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('loan_no')->unique();
            $table->string('lender_name');
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->decimal('principal', 18, 2);
            $table->decimal('principal_base', 18, 2);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('paid_amount_base', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->string('status')->default('unpaid');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('loan_id')->nullable()->after('purchase_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_id');
        });

        Schema::dropIfExists('loans');
    }
};
