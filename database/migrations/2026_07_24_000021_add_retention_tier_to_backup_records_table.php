<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_records', function (Blueprint $table) {
            $table->string('retention_tier')->default('manual')->after('trigger');
            $table->string('mirror_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('backup_records', function (Blueprint $table) {
            $table->dropColumn(['retention_tier', 'mirror_path']);
        });
    }
};
