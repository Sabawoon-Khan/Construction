<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Partner;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoPeopleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@yaqeen.tech')->firstOrFail();

        $users = [
            [
                'email' => 'pm@yaqeen.tech',
                'name' => 'Najibullah Ahmadi',
                'role' => 'Project Manager',
            ],
            [
                'email' => 'finance@yaqeen.tech',
                'name' => 'Farishta Noorzai',
                'role' => 'Finance',
            ],
            [
                'email' => 'shareholder@yaqeen.tech',
                'name' => 'Haji Abdur Rahman Barakzai',
                'role' => 'Shareholder',
            ],
            [
                'email' => 'staff@yaqeen.tech',
                'name' => 'Wahidullah Safi',
                'role' => 'Limited Employee',
            ],
        ];

        $logins = ['admin@yaqeen.tech' => $admin];

        foreach ($users as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );
            $user->syncRoles([$row['role']]);
            $logins[$row['email']] = $user;
        }

        $employees = [
            [
                'no' => 'EMP-001',
                'name' => 'Najibullah Ahmadi',
                'takhallus' => 'Ahmadi',
                'father_name' => 'Gul Mohammad',
                'email' => 'pm@yaqeen.tech',
                'phone' => '078 411 2201',
                'department' => 'Operations',
                'designation' => 'Project manager',
                'hours' => '08:00–17:00',
                'joined' => '2022-03-12',
                'salary' => 1800,
                'method' => 'bank',
                'login' => 'pm@yaqeen.tech',
            ],
            [
                'no' => 'EMP-002',
                'name' => 'Farishta Noorzai',
                'takhallus' => 'Noorzai',
                'father_name' => 'Abdul Qadir',
                'email' => 'finance@yaqeen.tech',
                'phone' => '079 330 1844',
                'department' => 'Finance',
                'designation' => 'Finance officer',
                'hours' => '08:30–16:30',
                'joined' => '2023-01-08',
                'salary' => 950,
                'method' => 'bank',
                'login' => 'finance@yaqeen.tech',
            ],
            [
                'no' => 'EMP-003',
                'name' => 'Wahidullah Safi',
                'takhallus' => 'Safi',
                'father_name' => 'Mirwais',
                'email' => 'staff@yaqeen.tech',
                'phone' => '070 552 9013',
                'department' => 'Field',
                'designation' => 'Site supervisor',
                'hours' => '07:00–16:00',
                'joined' => '2021-09-01',
                'salary' => 720,
                'method' => 'cash',
                'login' => 'staff@yaqeen.tech',
            ],
            [
                'no' => 'EMP-004',
                'name' => 'Ziaulhaq Popalzai',
                'takhallus' => 'Popalzai',
                'father_name' => 'Haji Nazar',
                'email' => 'ziaulhaq.popalzai@yaqeen.tech',
                'phone' => '078 664 1180',
                'department' => 'Operations',
                'designation' => 'Site engineer',
                'hours' => '07:00–16:00',
                'joined' => '2022-11-20',
                'salary' => 1100,
                'method' => 'bank',
                'login' => null,
            ],
            [
                'no' => 'EMP-005',
                'name' => 'Shukria Mohammadi',
                'takhallus' => 'Mohammadi',
                'father_name' => 'Ghulam Sakhi',
                'email' => 'shukria.mohammadi@yaqeen.tech',
                'phone' => '077 218 4409',
                'department' => 'Procurement',
                'designation' => 'Procurement officer',
                'hours' => '08:00–16:00',
                'joined' => '2023-06-04',
                'salary' => 680,
                'method' => 'bank',
                'login' => null,
            ],
            [
                'no' => 'EMP-006',
                'name' => 'Ghulam Haidar Kochi',
                'takhallus' => 'Kochi',
                'father_name' => 'Dad Mohammad',
                'email' => null,
                'phone' => '070 901 3345',
                'department' => 'Field',
                'designation' => 'Masonry foreman',
                'hours' => '07:00–16:00',
                'joined' => '2020-04-15',
                'salary' => 480,
                'method' => 'cash',
                'login' => null,
            ],
            [
                'no' => 'EMP-007',
                'name' => 'Nasima Sharifi',
                'takhallus' => 'Sharifi',
                'father_name' => 'Mohammad Sharif',
                'email' => 'nasima.sharifi@yaqeen.tech',
                'phone' => '079 145 7722',
                'department' => 'Administration',
                'designation' => 'Office administrator',
                'hours' => '08:30–16:30',
                'joined' => '2024-02-01',
                'salary' => 520,
                'method' => 'bank',
                'login' => null,
            ],
            [
                'no' => 'EMP-008',
                'name' => 'Karimullah Andar',
                'takhallus' => 'Andar',
                'father_name' => 'Juma Khan',
                'email' => null,
                'phone' => '078 300 6612',
                'department' => 'Field',
                'designation' => 'Driver',
                'hours' => '07:00–17:00',
                'joined' => '2021-12-10',
                'salary' => 380,
                'method' => 'cash',
                'login' => null,
            ],
        ];

        foreach ($employees as $row) {
            Employee::query()->updateOrCreate(
                ['employee_no' => $row['no']],
                [
                    'name' => $row['name'],
                    'takhallus' => $row['takhallus'],
                    'father_name' => $row['father_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'department' => $row['department'],
                    'designation' => $row['designation'],
                    'working_hours' => $row['hours'],
                    'joining_date' => $row['joined'],
                    'basic_salary' => $row['salary'],
                    'basic_salary_base' => $row['salary'],
                    'salary_payment_method' => $row['method'],
                    'currency' => 'USD',
                    'exchange_rate' => 1,
                    'status' => 'active',
                    'login_user_id' => $row['login'] ? $logins[$row['login']]->id : null,
                    'user_id' => $admin->id,
                    'notes' => null,
                ],
            );
        }

        $customers = [
            [
                'no' => 'C-001',
                'name' => 'Ahmad Shah Karimi',
                'takhallus' => 'Karimi',
                'father_name' => 'Abdullah Karimi',
                'phone' => '070 221 8841',
                'email' => 'ahmad.karimi@gmail.com',
                'national_id' => '1190-3345-2281',
                'address' => 'Taimani, District 4, Kabul',
                'registered' => '2024-03-18',
            ],
            [
                'no' => 'C-002',
                'name' => 'Fatima Nazari',
                'takhallus' => 'Nazari',
                'father_name' => 'Mohammad Ismail',
                'phone' => '079 440 1120',
                'email' => 'fatima.nazari@yahoo.com',
                'national_id' => '2201-1188-9044',
                'address' => 'Kart-e-Char, District 6, Kabul',
                'registered' => '2024-05-02',
            ],
            [
                'no' => 'C-003',
                'name' => 'Mohammad Nabi Wardak',
                'takhallus' => 'Wardak',
                'father_name' => 'Haji Gulab',
                'phone' => '078 915 3308',
                'email' => null,
                'national_id' => '3312-7701-4410',
                'address' => 'Khair Khana, District 11, Kabul',
                'registered' => '2024-07-21',
            ],
            [
                'no' => 'C-004',
                'name' => 'Zahra Ahmadi',
                'takhallus' => 'Ahmadi',
                'father_name' => 'Sayed Hassan',
                'phone' => '077 608 2294',
                'email' => 'zahra.ahmadi@outlook.com',
                'national_id' => '4488-1209-7732',
                'address' => 'Wazir Akbar Khan, District 10, Kabul',
                'registered' => '2024-09-09',
            ],
            [
                'no' => 'C-005',
                'name' => 'Haji Abdul Ghani Hotak',
                'takhallus' => 'Hotak',
                'father_name' => 'Mullah Janan',
                'phone' => '070 118 5526',
                'email' => null,
                'national_id' => '5501-9902-1187',
                'address' => 'Qala-e-Fathullah, District 10, Kabul',
                'registered' => '2025-01-14',
            ],
            [
                'no' => 'C-006',
                'name' => 'Maryam Sultani',
                'takhallus' => 'Sultani',
                'father_name' => 'Najib Sultani',
                'phone' => '079 774 0015',
                'email' => 'maryam.sultani@gmail.com',
                'national_id' => '6610-3344-2289',
                'address' => 'Shahr-e-Naw, District 10, Kabul',
                'registered' => '2025-03-03',
            ],
            [
                'no' => 'C-007',
                'name' => 'Dr. Homayoun Osmani',
                'takhallus' => 'Osmani',
                'father_name' => 'Mohammad Akbar',
                'phone' => '078 200 9917',
                'email' => 'homayoun.osmani@kabulhealth.af',
                'national_id' => '7722-5510-8841',
                'address' => 'Karte Parwan, District 4, Kabul',
                'registered' => '2025-04-27',
            ],
            [
                'no' => 'C-008',
                'name' => 'Laila Barakzai',
                'takhallus' => 'Barakzai',
                'father_name' => 'Haji Samim',
                'phone' => '070 333 4408',
                'email' => 'laila.barakzai@gmail.com',
                'national_id' => '8833-2201-1190',
                'address' => 'Darulaman, District 6, Kabul',
                'registered' => '2025-06-11',
            ],
            [
                'no' => 'C-009',
                'name' => 'Nasrullah Shinwari',
                'takhallus' => 'Shinwari',
                'father_name' => 'Malik Zarin',
                'phone' => '077 441 2280',
                'email' => null,
                'national_id' => '9944-1188-3302',
                'address' => 'Microrayon 3, District 9, Kabul',
                'registered' => '2025-08-19',
            ],
        ];

        foreach ($customers as $row) {
            Customer::query()->updateOrCreate(
                ['customer_no' => $row['no']],
                [
                    'name' => $row['name'],
                    'takhallus' => $row['takhallus'],
                    'father_name' => $row['father_name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'national_id' => $row['national_id'],
                    'address' => $row['address'],
                    'registered_at' => $row['registered'],
                    'type' => 'retail',
                    'status' => 'active',
                    'user_id' => $admin->id,
                    'notes' => 'Walk-in buyer. Prefers cash for first installment, bank for later payments.',
                ],
            );
        }

        $partners = [
            [
                'no' => 'PTR-001',
                'name' => 'Yaqeen',
                'phone' => '078 200 1010',
                'email' => 'admin@yaqeen.tech',
                'national_id' => '1001-2002-3003',
                'address' => 'Shahr-e-Naw, Kabul',
                'user' => 'admin@yaqeen.tech',
                'notes' => 'Founding shareholder and managing owner.',
            ],
            [
                'no' => 'PTR-002',
                'name' => 'Haji Abdur Rahman Barakzai',
                'phone' => '070 880 4412',
                'email' => 'shareholder@yaqeen.tech',
                'national_id' => '2110-3321-4455',
                'address' => 'Kart-e-Naw, District 8, Kabul',
                'user' => 'shareholder@yaqeen.tech',
                'notes' => 'Land partner on Kart-e-Char and Khair Khana.',
            ],
            [
                'no' => 'PTR-003',
                'name' => 'Mohammad Haroon Osmani',
                'phone' => '079 112 6670',
                'email' => 'haroon.osmani@gmail.com',
                'national_id' => '3221-4432-5566',
                'address' => 'Wazir Akbar Khan, Kabul',
                'user' => null,
                'notes' => 'Cash investor. Reviews monthly profit statements.',
            ],
        ];

        foreach ($partners as $row) {
            Partner::query()->updateOrCreate(
                ['partner_no' => $row['no']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'national_id' => $row['national_id'],
                    'address' => $row['address'],
                    'notes' => $row['notes'],
                    'user_id' => $row['user'] ? $logins[$row['user']]->id : null,
                    'created_by' => $admin->id,
                ],
            );
        }

        $suppliers = [
            [
                'no' => 'SUP-001',
                'name' => 'Spin Ghar Cement Depot',
                'contact' => 'Ghulam Nabi Shinwari',
                'phone' => '070 600 2218',
                'email' => 'sales@spinghar-cement.af',
                'address' => 'Pul-e-Charkhi industrial park, Kabul',
                'notes' => 'Main cement supplier. 30-day credit on bulk orders.',
            ],
            [
                'no' => 'SUP-002',
                'name' => 'Kabul Steel & Rebar',
                'contact' => 'Ziaulhaq Stanikzai',
                'phone' => '078 445 1190',
                'email' => 'orders@kabulsteel.af',
                'address' => 'Bagrami industrial park, Kabul',
                'notes' => '12mm and 16mm rebar. Weighbridge on site.',
            ],
            [
                'no' => 'SUP-003',
                'name' => 'Herat Tile & Ceramic',
                'contact' => 'Nargis Ahmadi',
                'phone' => '079 330 7741',
                'email' => 'nargis@herattile.af',
                'address' => 'Jade Maiwand, District 2, Kabul showroom',
                'notes' => 'Floor and wall tiles. Samples kept in Shahr-e-Naw office.',
            ],
            [
                'no' => 'SUP-004',
                'name' => 'Logar Sand & Aggregate',
                'contact' => 'Malik Raz Mohammad',
                'phone' => '077 218 9904',
                'email' => null,
                'address' => 'Mohammad Agha, Logar',
                'notes' => 'River sand and 20mm gravel. Dump trucks billed per trip.',
            ],
        ];

        foreach ($suppliers as $row) {
            Supplier::query()->updateOrCreate(
                ['supplier_no' => $row['no']],
                [
                    'name' => $row['name'],
                    'contact_person' => $row['contact'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'notes' => $row['notes'],
                    'user_id' => $admin->id,
                ],
            );
        }
    }
}
