<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('registered_at')->nullable()->after('address');
            $table->string('type')->default('retail')->after('registered_at');
            $table->string('status')->default('active')->after('type');
        });

        foreach (DB::table('customers')->whereNull('registered_at')->select('id', 'created_at')->cursor() as $row) {
            DB::table('customers')->where('id', $row->id)->update([
                'registered_at' => $row->created_at ? substr((string) $row->created_at, 0, 10) : now()->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['registered_at', 'type', 'status']);
        });
    }
};
