<?php

use App\Models\Company;
use App\Models\OrgSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NAME_FA = 'شاهین صمیم بارکزی';

    private const NAME_EN = 'Shahin Samim Barakzai';

    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
        });

        Company::query()->each(function (Company $company): void {
            $updates = [];

            if (! filled($company->name_en)) {
                $updates['name_en'] = self::NAME_EN;
            }

            if (! filled($company->name) || $company->name === config('app.name')) {
                $updates['name'] = self::NAME_FA;
            }

            if ($updates !== []) {
                $company->update($updates);
            }
        });

        $currentAppName = (string) OrgSetting::getValue('app_name', '');
        if ($currentAppName === '' || $currentAppName === (string) config('app.name')) {
            OrgSetting::setValue('app_name', self::NAME_FA);
        }

        if (! filled(OrgSetting::getValue('app_name_en'))) {
            OrgSetting::setValue('app_name_en', self::NAME_EN);
        }
    }

    public function down(): void
    {
        OrgSetting::forget('app_name_en');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('name_en');
        });
    }
};
