<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedTinyInteger('corridors')->nullable()->after('balconies');
            $table->unsignedTinyInteger('courtyards')->nullable()->after('corridors');
            $table->unsignedTinyInteger('parkings')->nullable()->after('courtyards');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['corridors', 'courtyards', 'parkings']);
        });
    }
};
