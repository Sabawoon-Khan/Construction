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
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->syncRoles(['Owner']);

        Company::query()->firstOrCreate(
            ['name' => config('app.name', 'Yaqeen Kit')],
            [
                'legal_name' => config('app.name', 'Yaqeen Kit'),
                'base_currency' => 'USD',
                'is_active' => true,
            ],
        );

        OrgSetting::setValue('app_name', config('app.name', 'Yaqeen Kit'));
        OrgSetting::setValue('default_locale', config('app.locale', 'fa'));
        OrgSetting::setValue('calendar_system', 'jalali');

        $this->call(KitDemoSeeder::class);

        app(NotificationService::class)->notifyUser(
            $admin,
            'Welcome to the starter kit',
            'You are signed in as Owner. Configure users, roles, and settings from the sidebar.',
            'success',
            '/settings/general',
        );
    }
}
