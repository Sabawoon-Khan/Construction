<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_employee', function (Blueprint $table) {
            $table->date('started_at')->nullable()->after('employee_id');
            $table->string('role_on_project', 100)->nullable()->after('started_at');
            $table->string('working_hours', 100)->nullable()->after('role_on_project');
            $table->text('notes')->nullable()->after('working_hours');
        });
    }

    public function down(): void
    {
        Schema::table('project_employee', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'role_on_project', 'working_hours', 'notes']);
        });
    }
};
