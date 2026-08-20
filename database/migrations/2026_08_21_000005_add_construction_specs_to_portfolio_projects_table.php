<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->unsignedInteger('total_apartments')->nullable()->after('duration');
            $table->unsignedSmallInteger('floors')->nullable()->after('total_apartments');
            $table->string('plot_size')->nullable()->after('floors');
            $table->unsignedInteger('parking_spaces')->nullable()->after('plot_size');
            $table->string('project_status', 32)->nullable()->after('parking_spaces');
            $table->unsignedSmallInteger('year')->nullable()->after('project_status');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->dropColumn([
                'total_apartments',
                'floors',
                'plot_size',
                'parking_spaces',
                'project_status',
                'year',
            ]);
        });
    }
};
