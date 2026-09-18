<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ExchangeRate;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Models\IncomeCategory;
use App\Models\LookupItem;
use App\Models\Material;
use App\Models\MaterialUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@yaqeen.tech')->value('id');

        $departments = [
            'Administration',
            'Finance',
            'Operations',
            'Field',
            'Procurement',
        ];

        foreach ($departments as $index => $name) {
            LookupItem::query()->updateOrCreate(
                ['group' => 'departments', 'name' => $name],
                ['sort_order' => $index + 1, 'is_active' => true, 'user_id' => $adminId],
            );
        }

        ExpenseCategory::ensureDefaults();

        $incomeCategories = [
            ['code' => 'general', 'name' => 'General', 'description' => 'Default income category'],
            ['code' => 'other', 'name' => 'Other income', 'description' => 'Parking, late fees, and miscellaneous receipts'],
            ['code' => 'rental', 'name' => 'Site rental', 'description' => 'Temporary rental of unused plots or shops'],
        ];

        foreach ($incomeCategories as $row) {
            IncomeCategory::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_active' => true,
                    'user_id' => $adminId,
                ],
            );
        }

        foreach (['Day', 'Trip', 'Month', 'Bag', 'Cubic meter', 'Piece', 'Hour'] as $unitName) {
            ExpenseUnit::query()->firstOrCreate(
                ['unit_name' => $unitName],
                ['user_id' => $adminId],
            );
        }

        $materialUnits = [
            'Bag' => MaterialUnit::findOrCreateNamed('Bag'),
            'Ton' => MaterialUnit::findOrCreateNamed('Ton'),
            'Piece' => MaterialUnit::findOrCreateNamed('Piece'),
            'Cubic meter' => MaterialUnit::findOrCreateNamed('Cubic meter'),
            'Square meter' => MaterialUnit::findOrCreateNamed('Square meter'),
            'Liter' => MaterialUnit::findOrCreateNamed('Liter'),
            'Meter' => MaterialUnit::findOrCreateNamed('Meter'),
        ];

        $materials = [
            ['code' => 'CEM-50', 'name' => 'Ghori cement 50kg', 'unit' => 'Bag', 'cost' => 6.40, 'min' => 80],
            ['code' => 'REB-12', 'name' => 'Rebar 12mm', 'unit' => 'Ton', 'cost' => 620, 'min' => 4],
            ['code' => 'REB-16', 'name' => 'Rebar 16mm', 'unit' => 'Ton', 'cost' => 640, 'min' => 2],
            ['code' => 'BRK-RD', 'name' => 'Red fired brick', 'unit' => 'Piece', 'cost' => 0.12, 'min' => 5000],
            ['code' => 'SND-RV', 'name' => 'River sand', 'unit' => 'Cubic meter', 'cost' => 18, 'min' => 20],
            ['code' => 'GRV-20', 'name' => 'Crushed gravel 20mm', 'unit' => 'Cubic meter', 'cost' => 22, 'min' => 15],
            ['code' => 'TIL-60', 'name' => 'Ceramic floor tile 60x60', 'unit' => 'Square meter', 'cost' => 8.50, 'min' => 100],
            ['code' => 'PNT-WH', 'name' => 'Interior white paint', 'unit' => 'Liter', 'cost' => 3.20, 'min' => 40],
            ['code' => 'CBL-25', 'name' => 'Electrical cable 2.5mm', 'unit' => 'Meter', 'cost' => 0.85, 'min' => 200],
        ];

        foreach ($materials as $row) {
            Material::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'material_unit_id' => $materialUnits[$row['unit']]->id,
                    'minimum_stock' => $row['min'],
                    'unit_cost' => $row['cost'],
                    'unit_cost_base' => $row['cost'],
                    'currency' => 'USD',
                    'exchange_rate' => 1,
                    'is_active' => true,
                    'user_id' => $adminId,
                ],
            );
        }

        $accounts = [
            [
                'name' => 'Office cash box',
                'bank_name' => null,
                'account_number' => null,
                'currency' => 'USD',
                'opening_balance' => 12500,
                'is_cash' => true,
            ],
            [
                'name' => 'Azizi Bank — USD operating',
                'bank_name' => 'Azizi Bank',
                'account_number' => '001-220-88421',
                'currency' => 'USD',
                'opening_balance' => 186000,
                'is_cash' => false,
            ],
            [
                'name' => 'AIB — AFN payroll',
                'bank_name' => 'Afghanistan International Bank',
                'account_number' => '445-019-77210',
                'currency' => 'AFN',
                'opening_balance' => 850000,
                'is_cash' => false,
            ],
        ];

        foreach ($accounts as $row) {
            $rate = $row['currency'] === 'AFN' ? 0.014286 : 1;
            BankAccount::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'bank_name' => $row['bank_name'],
                    'account_number' => $row['account_number'],
                    'currency' => $row['currency'],
                    'exchange_rate' => $rate,
                    'opening_balance' => $row['opening_balance'],
                    'opening_balance_base' => round($row['opening_balance'] * $rate, 2),
                    'is_cash' => $row['is_cash'],
                    'is_active' => true,
                    'user_id' => $adminId,
                ],
            );
        }

        ExchangeRate::query()->updateOrCreate(
            [
                'from_currency' => 'USD',
                'to_currency' => 'AFN',
                'effective_date' => '2024-01-01',
            ],
            ['rate' => 70, 'created_by' => $adminId],
        );

        ExchangeRate::query()->updateOrCreate(
            [
                'from_currency' => 'AFN',
                'to_currency' => 'USD',
                'effective_date' => '2024-01-01',
            ],
            ['rate' => 0.014286, 'created_by' => $adminId],
        );
    }
}
