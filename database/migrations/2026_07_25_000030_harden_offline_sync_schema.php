<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->after('user_agent');
            $table->index('personal_access_token_id');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('id');
            $table->unique('client_uuid');
        });

        Schema::table('sync_client_mutations', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->unique(['user_id', 'client_uuid']);
        });
    }

    public function down(): void
    {
        Schema::table('sync_client_mutations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'client_uuid']);
            $table->unique('client_uuid');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });

        Schema::table('sync_devices', function (Blueprint $table) {
            $table->dropIndex(['personal_access_token_id']);
            $table->dropColumn('personal_access_token_id');
        });
    }
};
