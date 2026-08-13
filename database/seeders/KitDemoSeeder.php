<?php

namespace Database\Seeders;

use App\Models\LookupItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class KitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['Administration', 'Finance', 'Operations', 'Field'];

        foreach ($departments as $index => $name) {
            LookupItem::query()->updateOrCreate(
                ['group' => 'departments', 'name' => $name],
                ['sort_order' => $index + 1, 'is_active' => true],
            );
        }

        LookupItem::query()->updateOrCreate(
            ['group' => 'categories', 'name' => 'General'],
            ['sort_order' => 1, 'is_active' => true],
        );

        if (! User::query()->where('email', 'staff@example.com')->exists()) {
            $staff = User::query()->create([
                'name' => 'Demo Staff',
                'email' => 'staff@example.com',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $staff->syncRoles(['User']);
        }
    }
}
