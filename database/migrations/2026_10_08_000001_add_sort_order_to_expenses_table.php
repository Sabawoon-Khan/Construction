<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('status');
            $table->index(['project_id', 'sort_order']);
        });

        $projectIds = DB::table('expenses')
            ->select('project_id')
            ->distinct()
            ->pluck('project_id');

        foreach ($projectIds as $projectId) {
            $query = DB::table('expenses')->orderBy('id');

            if ($projectId === null) {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $projectId);
            }

            $order = 1;
            foreach ($query->pluck('id') as $id) {
                DB::table('expenses')->where('id', $id)->update(['sort_order' => $order++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
