<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('from_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['from_project_id']);
            $table->dropForeign(['to_project_id']);
            $table->dropColumn(['from_project_id', 'to_project_id']);
        });
    }
};
