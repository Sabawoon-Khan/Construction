<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('authentication_logs', 'ip_addresses')) {
                $table->json('ip_addresses')->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('authentication_logs', 'request_method')) {
                $table->string('request_method', 16)->nullable()->after('guard');
            }

            if (! Schema::hasColumn('authentication_logs', 'request_path')) {
                $table->string('request_path')->nullable()->after('request_method');
            }

            if (! Schema::hasColumn('authentication_logs', 'referer')) {
                $table->text('referer')->nullable()->after('request_path');
            }

            if (! Schema::hasColumn('authentication_logs', 'accept_language')) {
                $table->string('accept_language')->nullable()->after('referer');
            }

            if (! Schema::hasColumn('authentication_logs', 'country')) {
                $table->string('country')->nullable()->after('accept_language');
            }

            if (! Schema::hasColumn('authentication_logs', 'country_code')) {
                $table->string('country_code', 8)->nullable()->index()->after('country');
            }

            if (! Schema::hasColumn('authentication_logs', 'region')) {
                $table->string('region')->nullable()->after('country_code');
            }

            if (! Schema::hasColumn('authentication_logs', 'city')) {
                $table->string('city')->nullable()->index()->after('region');
            }

            if (! Schema::hasColumn('authentication_logs', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('city');
            }

            if (! Schema::hasColumn('authentication_logs', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('authentication_logs', 'timezone')) {
                $table->string('timezone')->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('authentication_logs', 'isp')) {
                $table->string('isp')->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('authentication_logs', 'org')) {
                $table->string('org')->nullable()->after('isp');
            }

            if (! Schema::hasColumn('authentication_logs', 'asn')) {
                $table->string('asn')->nullable()->after('org');
            }

            if (! Schema::hasColumn('authentication_logs', 'is_private_ip')) {
                $table->boolean('is_private_ip')->default(false)->after('asn');
            }

            if (! Schema::hasColumn('authentication_logs', 'location_label')) {
                $table->string('location_label')->nullable()->after('is_private_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $columns = [
                'ip_addresses',
                'request_method',
                'request_path',
                'referer',
                'accept_language',
                'country',
                'country_code',
                'region',
                'city',
                'latitude',
                'longitude',
                'timezone',
                'isp',
                'org',
                'asn',
                'is_private_ip',
                'location_label',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('authentication_logs', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
