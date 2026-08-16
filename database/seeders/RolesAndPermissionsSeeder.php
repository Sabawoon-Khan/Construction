<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Services\MisPermissionRegistrar;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(MisPermissionRegistrar::class)->sync();
        ExpenseCategory::ensureDefaults();

        $all = Permission::all();

        $owner = Role::findOrCreate('Owner');
        $owner->syncPermissions($all);

        $admin = Role::findOrCreate('Admin');
        $admin->syncPermissions($all);

        $projectManager = Role::findOrCreate('Project Manager');
        $projectManager->syncPermissions([
            'dashboard.view',
            'projects.view',
            'projects.create',
            'projects.edit',
            'units.view',
            'units.create',
            'units.edit',
            'sales.view',
            'customers.view',
            'expenses.view',
            'expenses.create',
            'purchases.view',
            'purchases.create',
            'inventory.view',
            'inventory.create',
            'suppliers.view',
            'materials.view',
            'field_reports.view',
            'field_reports.create',
            'field_reports.edit',
            'field_notes.view',
            'field_notes.create',
            'field_notes.edit',
            'employees.view',
            'attendance.view',
            'salaries.view',
        ]);

        $finance = Role::findOrCreate('Finance');
        $finance->syncPermissions([
            'dashboard.view',
            'projects.view',
            'units.view',
            'customers.view',
            'customers.create',
            'customers.edit',
            'partners.view',
            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'incomes.view',
            'incomes.create',
            'incomes.edit',
            'sales.view',
            'sales.create',
            'sales.edit',
            'payments.view',
            'vouchers.view',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'materials.view',
            'materials.create',
            'materials.edit',
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'employees.view',
            'employees.create',
            'employees.edit',
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'salaries.view',
            'salaries.create',
            'salaries.edit',
        ]);

        $shareholder = Role::findOrCreate('Shareholder');
        $shareholder->syncPermissions([
            'dashboard.view',
            'projects.view',
            'units.view',
            'partners.view',
        ]);

        $limited = Role::findOrCreate('Limited Employee');
        $limited->syncPermissions([
            'dashboard.view',
            'projects.view',
            'units.view',
            'expenses.view',
            'expenses.create',
            'purchases.view',
            'inventory.view',
            'employees.view',
            'attendance.view',
            'salaries.view',
        ]);

        $user = Role::findOrCreate('User');
        $user->syncPermissions([
            'dashboard.view',
        ]);
    }
}
