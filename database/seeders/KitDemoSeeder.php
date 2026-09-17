<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class KitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@yaqeen.tech')->firstOrFail();
        Auth::login($admin);

        $this->call([
            DemoCatalogSeeder::class,
            DemoPeopleSeeder::class,
            DemoProjectsSeeder::class,
            DemoFinanceSeeder::class,
        ]);

        Auth::logout();
    }
}
