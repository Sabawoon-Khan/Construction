<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('expenses', 'per_price')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('per_price', 18, 2)->nullable()->after('item_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('expenses', 'per_price')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('per_price');
        });
    }
};
