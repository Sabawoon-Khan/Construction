<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        DB::table('payments')
            ->whereNotNull('sale_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sales')
                    ->whereColumn('sales.id', 'payments.sale_id')
                    ->whereNull('sales.deleted_at');
            })
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
