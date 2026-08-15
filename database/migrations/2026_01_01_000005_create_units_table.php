<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('floor_number')->default(1);
            $table->string('unit_no');
            $table->string('type')->default('apartment');
            $table->decimal('area', 12, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->unsignedTinyInteger('toilets')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('halls')->nullable();
            $table->unsignedTinyInteger('kitchens')->nullable();
            $table->unsignedTinyInteger('balconies')->nullable();
            $table->decimal('list_price', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'unit_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
