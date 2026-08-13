<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('agreed_share_percentage', 8, 4)->nullable();
            $table->date('invested_at');
            $table->string('type')->default('investment');
            $table->string('reference_no')->nullable();
            $table->string('status')->default('approved');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_investments');
    }
};
