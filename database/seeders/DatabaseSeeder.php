<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OrgSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@yaqeen.tech'],
            [
                'name' => 'Yaqeen',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->syncRoles(['Owner']);

        $company = Company::query()->first();
        $profile = [
            'name' => 'یقین',
            'name_en' => 'Yaqeen',
            'legal_name' => 'یقین',
            'registration_no' => 'YQN-CONST-1398-0412',
            'phone' => '078 200 1010',
            'email' => 'info@yaqeen.tech',
            'address' => 'شهر نو، کابل، افغانستان',
            'address_en' => 'Shahr-e-Naw, Kabul, Afghanistan',
            'base_currency' => 'USD',
            'is_active' => true,
        ];

        if ($company) {
            $company->update($profile);
        } else {
            $company = Company::query()->create($profile);
        }

        $company->syncBrandSettings();
        OrgSetting::setValue('default_locale', config('app.locale', 'fa'));
        OrgSetting::setValue('calendar_system', 'jalali');

        $this->call(KitDemoSeeder::class);

        app(NotificationService::class)->notifyUser(
            $admin,
            'Welcome to Yaqeen',
            'The demo is loaded with Kabul projects, buyers, staff, and money in/out. Sign in as Owner and walk the sidebar.',
            'success',
            '/dashboard',
        );
    }
}
