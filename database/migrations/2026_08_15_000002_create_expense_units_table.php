<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_name')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_units');
    }
};
