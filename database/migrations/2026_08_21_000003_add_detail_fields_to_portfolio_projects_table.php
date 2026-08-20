<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('summary');
            $table->string('client_name')->nullable()->after('category');
            $table->string('scope')->nullable()->after('client_name');
            $table->string('area_size')->nullable()->after('scope');
            $table->string('duration')->nullable()->after('area_size');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'client_name',
                'scope',
                'area_size',
                'duration',
            ]);
        });
    }
};
