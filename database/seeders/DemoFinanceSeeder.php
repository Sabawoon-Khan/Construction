<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\Material;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\Salary;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\FinanceDefaults;
use App\Services\LoanService;
use App\Services\PurchaseService;
use App\Services\SaleInstallmentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        if (Sale::query()->where('sale_no', 'SAL-001')->exists()) {
            return;
        }

        $this->seedInvestments();
        $this->seedSales();
        $this->seedPurchasesAndStock();
        $this->seedExpenses();
        $this->seedIncomes();
        $this->seedLoans();
        $this->seedDistributions();
        $this->seedAttendance();
        $this->seedSalaries();
    }

    private function seedInvestments(): void
    {
        $yaqeen = Partner::query()->where('partner_no', 'PTR-001')->firstOrFail();
        $rahman = Partner::query()->where('partner_no', 'PTR-002')->firstOrFail();
        $haroon = Partner::query()->where('partner_no', 'PTR-003')->firstOrFail();

        $khairKhana = Project::query()->where('code', '1')->firstOrFail();
        $shahrNow = Project::query()->where('code', '2')->firstOrFail();
        $darulaman = Project::query()->where('code', '3')->firstOrFail();
        $karteChar = Project::query()->where('code', '4')->firstOrFail();

        $rows = [
            [$khairKhana, $yaqeen, 200000, '2024-04-20', 'INV-KH-01', 'Opening land and structure capital.'],
            [$khairKhana, $rahman, 120000, '2024-04-22', 'INV-KH-02', 'Land share converted to cash contribution.'],
            [$khairKhana, $haroon, 80000, '2024-05-06', 'INV-KH-03', 'Cash in for first three floors.'],
            [$shahrNow, $yaqeen, 150000, '2023-06-08', 'INV-SN-01', 'Tower land and excavation.'],
            [$shahrNow, $haroon, 100000, '2023-06-15', 'INV-SN-02', 'Cash partner on Shahr-e-Naw.'],
            [$darulaman, $yaqeen, 70000, '2025-11-05', 'INV-DA-01', 'Plot purchase and wall.'],
            [$darulaman, $rahman, 30000, '2025-11-12', 'INV-DA-02', 'Boundary and site office.'],
            [$karteChar, $yaqeen, 80000, '2022-02-14', 'INV-KC-01', 'Compound land.'],
            [$karteChar, $rahman, 80000, '2022-02-14', 'INV-KC-02', 'Equal land partner.'],
        ];

        foreach ($rows as [$project, $partner, $amount, $date, $reference, $notes]) {
            PartnerInvestment::query()->create([
                'project_id' => $project->id,
                'partner_id' => $partner->id,
                'amount' => $amount,
                'amount_base' => $amount,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'agreed_share_percentage' => $partner->ownershipOnProject($project->id),
                'invested_at' => $date,
                'type' => 'investment',
                'reference_no' => $reference,
                'status' => 'approved',
                'notes' => $notes,
            ]);
        }
    }

    private function seedSales(): void
    {
        $customers = Customer::query()->get()->keyBy('customer_no');
        $units = Unit::query()->with('project')->get()->keyBy(fn (Unit $unit) => $unit->project->code.'|'.$unit->unit_no);

        $this->createSale(
            saleNo: 'SAL-001',
            unit: $units['1|A-101'],
            customer: $customers['C-001'],
            contract: '2025-02-10',
            discount: 2000,
            status: 'active',
            terms: 'Down payment then three equal installments every two months.',
            installments: [
                ['due' => '2025-02-10', 'amount' => 20000, 'paid' => 20000, 'method' => 'cash', 'notes' => 'Down payment at contract signing'],
                ['due' => '2025-04-10', 'amount' => 12000, 'paid' => 12000, 'method' => 'bank', 'notes' => 'Second installment — Azizi Bank'],
                ['due' => '2025-06-10', 'amount' => 12000, 'paid' => 0, 'method' => 'bank', 'notes' => null],
                ['due' => '2025-08-10', 'amount' => 12000, 'paid' => 0, 'method' => 'bank', 'notes' => null],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-002',
            unit: $units['1|A-102'],
            customer: $customers['C-002'],
            contract: '2024-11-18',
            discount: 0,
            status: 'active',
            terms: 'Paid in two transfers.',
            installments: [
                ['due' => '2024-11-18', 'amount' => 40000, 'paid' => 40000, 'method' => 'bank', 'notes' => 'First transfer'],
                ['due' => '2025-01-18', 'amount' => 39000, 'paid' => 39000, 'method' => 'bank', 'notes' => 'Balance cleared'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-003',
            unit: $units['1|A-302'],
            customer: $customers['C-003'],
            contract: '2025-08-04',
            discount: 1500,
            status: 'active',
            terms: 'Cash down payment. Remainder on handover.',
            installments: [
                ['due' => '2025-08-04', 'amount' => 25000, 'paid' => 25000, 'method' => 'cash', 'notes' => 'Down payment'],
                ['due' => '2026-12-01', 'amount' => 58500, 'paid' => 0, 'method' => 'cash', 'notes' => 'Balance on handover'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-004',
            unit: $units['2|B-101'],
            customer: $customers['C-004'],
            contract: '2025-01-22',
            discount: 0,
            status: 'active',
            terms: 'Full payment before handover.',
            installments: [
                ['due' => '2025-01-22', 'amount' => 74000, 'paid' => 74000, 'method' => 'bank', 'notes' => 'Paid in full'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-005',
            unit: $units['2|B-102'],
            customer: $customers['C-005'],
            contract: '2025-03-09',
            discount: 3000,
            status: 'active',
            terms: 'Four quarterly installments.',
            installments: [
                ['due' => '2025-03-09', 'amount' => 25000, 'paid' => 25000, 'method' => 'cash', 'notes' => 'Down payment'],
                ['due' => '2025-06-09', 'amount' => 23333, 'paid' => 23333, 'method' => 'bank', 'notes' => 'Second quarter'],
                ['due' => '2025-09-09', 'amount' => 23333, 'paid' => 0, 'method' => 'bank', 'notes' => null],
                ['due' => '2025-12-09', 'amount' => 23334, 'paid' => 0, 'method' => 'bank', 'notes' => null],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-006',
            unit: $units['2|B-201'],
            customer: $customers['C-006'],
            contract: '2025-07-16',
            discount: 1000,
            status: 'active',
            terms: 'Deposit now, balance before keys.',
            installments: [
                ['due' => '2025-07-16', 'amount' => 20000, 'paid' => 20000, 'method' => 'bank', 'notes' => 'Booking deposit'],
                ['due' => '2026-02-01', 'amount' => 55000, 'paid' => 0, 'method' => 'bank', 'notes' => 'Balance before handover'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-007',
            unit: $units['4|C-101'],
            customer: $customers['C-007'],
            contract: '2024-08-12',
            discount: 0,
            status: 'active',
            terms: 'Paid before handover.',
            installments: [
                ['due' => '2024-08-12', 'amount' => 45000, 'paid' => 45000, 'method' => 'bank', 'notes' => 'Cleared'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-008',
            unit: $units['4|C-102'],
            customer: $customers['C-008'],
            contract: '2024-09-03',
            discount: 500,
            status: 'active',
            terms: 'Family purchase. Paid in cash and bank.',
            installments: [
                ['due' => '2024-09-03', 'amount' => 20000, 'paid' => 20000, 'method' => 'cash', 'notes' => 'Cash down'],
                ['due' => '2024-11-03', 'amount' => 24500, 'paid' => 24500, 'method' => 'bank', 'notes' => 'Balance'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-009',
            unit: $units['4|C-201'],
            customer: $customers['C-009'],
            contract: '2024-10-20',
            discount: 0,
            status: 'active',
            terms: 'Paid in full at contract.',
            installments: [
                ['due' => '2024-10-20', 'amount' => 56000, 'paid' => 56000, 'method' => 'cash', 'notes' => 'Cash sale'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-010',
            unit: $units['4|C-202'],
            customer: $customers['C-001'],
            contract: '2025-01-08',
            discount: 1000,
            status: 'active',
            terms: 'Second unit for Ahmad Shah’s brother.',
            installments: [
                ['due' => '2025-01-08', 'amount' => 55000, 'paid' => 55000, 'method' => 'bank', 'notes' => 'Paid in full'],
            ],
        );

        $this->createSale(
            saleNo: 'SAL-011',
            unit: $units['4|C-301'],
            customer: $customers['C-002'],
            contract: '2025-02-25',
            discount: 0,
            status: 'active',
            terms: 'Top-floor unit. Paid before keys.',
            installments: [
                ['due' => '2025-02-25', 'amount' => 61000, 'paid' => 61000, 'method' => 'bank', 'notes' => 'Paid in full'],
            ],
        );
    }

    /**
     * @param  list<array{due: string, amount: float|int, paid: float|int, method: string, notes: ?string}>  $installments
     */
    private function createSale(
        string $saleNo,
        Unit $unit,
        Customer $customer,
        string $contract,
        float $discount,
        string $status,
        string $terms,
        array $installments,
    ): void {
        $sale = Sale::query()->create([
            'project_id' => $unit->project_id,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'sale_no' => $saleNo,
            'contract_date' => $contract,
            'discount' => $discount,
            'discount_base' => $discount,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'status' => $status,
            'terms' => $terms,
            'notes' => 'Contract signed at the Shahr-e-Naw office. Copy with buyer.',
        ]);

        Unit::query()->whereKey($unit->id)->update(['status' => 'sold']);

        foreach ($installments as $index => $row) {
            $installment = SaleInstallment::query()->create([
                'sale_id' => $sale->id,
                'installment_no' => $index + 1,
                'due_date' => $row['due'],
                'amount' => $row['amount'],
                'amount_base' => $row['amount'],
                'paid_amount' => 0,
                'paid_amount_base' => 0,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'status' => 'pending',
                'notes' => $row['notes'],
            ]);

            if ((float) $row['paid'] > 0) {
                SaleInstallmentService::recordPayment(
                    $sale->fresh(['unit', 'installments']),
                    $installment,
                    (float) $row['paid'],
                    [
                        'payment_method' => $row['method'],
                        'paid_at' => $row['due'],
                        'reference_no' => $saleNo.'-I'.($index + 1),
                        'notes' => $row['notes'],
                    ],
                );
            }
        }
    }

    private function seedPurchasesAndStock(): void
    {
        $khairKhana = Project::query()->where('code', '1')->firstOrFail();
        $shahrNow = Project::query()->where('code', '2')->firstOrFail();

        $cement = Material::query()->where('code', 'CEM-50')->firstOrFail();
        $rebar12 = Material::query()->where('code', 'REB-12')->firstOrFail();
        $tiles = Material::query()->where('code', 'TIL-60')->firstOrFail();
        $sand = Material::query()->where('code', 'SND-RV')->firstOrFail();
        $gravel = Material::query()->where('code', 'GRV-20')->firstOrFail();
        $paint = Material::query()->where('code', 'PNT-WH')->firstOrFail();

        $spinGhar = Supplier::query()->where('supplier_no', 'SUP-001')->firstOrFail();
        $kabulSteel = Supplier::query()->where('supplier_no', 'SUP-002')->firstOrFail();
        $heratTile = Supplier::query()->where('supplier_no', 'SUP-003')->firstOrFail();
        $logar = Supplier::query()->where('supplier_no', 'SUP-004')->firstOrFail();

        PurchaseService::create([
            'project_id' => $khairKhana->id,
            'supplier_id' => $spinGhar->id,
            'purchase_date' => now()->subMonths(3)->toDateString(),
            'reference_no' => 'PO-CEM-1404-08',
            'notes' => 'Ghori 50kg for floors 5–6.',
            'currency' => 'USD',
            'paid_now' => 2560,
            'payment_method' => 'bank',
            'items' => [
                ['material_id' => $cement->id, 'quantity' => 400, 'unit_cost' => 6.40],
            ],
        ]);

        PurchaseService::create([
            'project_id' => $khairKhana->id,
            'supplier_id' => $kabulSteel->id,
            'purchase_date' => now()->subMonths(2)->toDateString(),
            'reference_no' => 'PO-REB-1404-09',
            'notes' => '12mm for floor 6 columns and slab.',
            'currency' => 'USD',
            'paid_now' => 3000,
            'payment_method' => 'bank',
            'items' => [
                ['material_id' => $rebar12->id, 'quantity' => 8, 'unit_cost' => 620],
            ],
        ]);

        PurchaseService::create([
            'project_id' => $shahrNow->id,
            'supplier_id' => $heratTile->id,
            'purchase_date' => now()->subMonth()->toDateString(),
            'reference_no' => 'PO-TIL-1404-10',
            'notes' => 'Floor tiles for remaining Shahr-e-Naw units.',
            'currency' => 'USD',
            'paid_now' => 0,
            'payment_method' => 'bank',
            'items' => [
                ['material_id' => $tiles->id, 'quantity' => 220, 'unit_cost' => 8.50],
            ],
        ]);

        PurchaseService::create([
            'project_id' => $khairKhana->id,
            'supplier_id' => $logar->id,
            'purchase_date' => now()->subWeeks(6)->toDateString(),
            'reference_no' => 'PO-AGG-1404-09',
            'notes' => 'Sand and gravel for floor 6 pour.',
            'currency' => 'USD',
            'paid_now' => 980,
            'payment_method' => 'cash',
            'items' => [
                ['material_id' => $sand->id, 'quantity' => 30, 'unit_cost' => 18],
                ['material_id' => $gravel->id, 'quantity' => 20, 'unit_cost' => 22],
            ],
        ]);

        PurchaseService::create([
            'project_id' => $shahrNow->id,
            'supplier_id' => $heratTile->id,
            'purchase_date' => now()->subWeeks(3)->toDateString(),
            'reference_no' => 'PO-PNT-1404-10',
            'notes' => 'Touch-up paint for snag list.',
            'currency' => 'USD',
            'paid_now' => 256,
            'payment_method' => 'cash',
            'items' => [
                ['material_id' => $paint->id, 'quantity' => 80, 'unit_cost' => 3.20],
            ],
        ]);

        PurchaseService::issue($khairKhana->id, $cement->id, 180, [
            'transaction_date' => now()->subMonths(2)->toDateString(),
            'reference_no' => 'ISS-KH-CEM-01',
            'notes' => 'Issued to floor 6 slab pour.',
        ]);

        PurchaseService::issue($khairKhana->id, $rebar12->id, 3.5, [
            'transaction_date' => now()->subMonths(2)->toDateString(),
            'reference_no' => 'ISS-KH-REB-01',
            'notes' => 'Issued to floor 6 columns.',
        ]);

        PurchaseService::issue($khairKhana->id, $sand->id, 18, [
            'transaction_date' => now()->subWeeks(5)->toDateString(),
            'reference_no' => 'ISS-KH-SND-01',
            'notes' => 'Issued with gravel for slab mix.',
        ]);
    }

    private function seedExpenses(): void
    {
        $khairKhana = Project::query()->where('code', '1')->firstOrFail();
        $shahrNow = Project::query()->where('code', '2')->firstOrFail();
        $darulaman = Project::query()->where('code', '3')->firstOrFail();

        $categories = ExpenseCategory::query()->get()->keyBy('code');
        $day = ExpenseUnit::query()->where('unit_name', 'Day')->first();
        $trip = ExpenseUnit::query()->where('unit_name', 'Trip')->first();
        $month = ExpenseUnit::query()->where('unit_name', 'Month')->first();
        $hour = ExpenseUnit::query()->where('unit_name', 'Hour')->first();

        $rows = [
            [
                'project' => $khairKhana,
                'category' => $categories['labor'],
                'unit' => $day,
                'date' => now()->subMonths(2)->toDateString(),
                'paid_to' => 'Ghulam Haidar Kochi',
                'item_amount' => 22,
                'per_price' => 18,
                'method' => 'cash',
                'description' => 'Masonry crew — 22 days on floor 5 brickwork.',
            ],
            [
                'project' => $khairKhana,
                'category' => $categories['labor'],
                'unit' => $day,
                'date' => now()->subMonth()->toDateString(),
                'paid_to' => 'Ghulam Haidar Kochi',
                'item_amount' => 18,
                'per_price' => 18,
                'method' => 'cash',
                'description' => 'Masonry crew — 18 days on floor 6.',
            ],
            [
                'project' => $khairKhana,
                'category' => $categories['transport'],
                'unit' => $trip,
                'date' => now()->subWeeks(6)->toDateString(),
                'paid_to' => 'Karimullah Andar',
                'item_amount' => 12,
                'per_price' => 45,
                'method' => 'cash',
                'description' => 'Dump truck — sand and gravel from Logar.',
            ],
            [
                'project' => $darulaman,
                'category' => $categories['machinery'],
                'unit' => $hour,
                'date' => now()->subWeeks(3)->toDateString(),
                'paid_to' => 'Kabul Machine Hire',
                'item_amount' => 16,
                'per_price' => 85,
                'method' => 'bank',
                'description' => 'Excavator for villa foundations and trench.',
            ],
            [
                'project' => $shahrNow,
                'category' => $categories['permits'],
                'unit' => null,
                'date' => now()->subMonths(4)->toDateString(),
                'paid_to' => 'Kabul Municipality',
                'item_amount' => 1,
                'per_price' => 2200,
                'method' => 'bank',
                'description' => 'Occupancy inspection fee.',
            ],
            [
                'project' => null,
                'category' => $categories['admin'],
                'unit' => $month,
                'date' => now()->subMonth()->startOfMonth()->toDateString(),
                'paid_to' => 'Haji Naim (office landlord)',
                'item_amount' => 1,
                'per_price' => 800,
                'method' => 'cash',
                'description' => 'Shahr-e-Naw office rent.',
            ],
            [
                'project' => $khairKhana,
                'category' => $categories['other'],
                'unit' => $day,
                'date' => now()->subWeeks(2)->toDateString(),
                'paid_to' => 'Site day labour',
                'item_amount' => 1,
                'per_price' => 45000,
                'method' => 'cash',
                'currency' => 'AFN',
                'description' => 'Local helpers for slab pour and curing.',
            ],
            [
                'project' => null,
                'category' => $categories['admin'],
                'unit' => $month,
                'date' => now()->startOfMonth()->toDateString(),
                'paid_to' => 'Afghan Telecom',
                'item_amount' => 1,
                'per_price' => 12000,
                'method' => 'bank',
                'currency' => 'AFN',
                'description' => 'Office internet and phone.',
            ],
        ];

        foreach ($rows as $row) {
            $currency = $row['currency'] ?? 'USD';
            Expense::query()->create(FinanceDefaults::expenseAttributes([
                'expense_category_id' => $row['category']->id,
                'expense_unit_id' => $row['unit']?->id,
                'expense_date' => $row['date'],
                'paid_to' => $row['paid_to'],
                'item_amount' => $row['item_amount'],
                'per_price' => $row['per_price'],
                'payment_method' => $row['method'],
                'currency' => $currency,
                'description' => $row['description'],
            ], $row['project']?->id));
        }
    }

    private function seedIncomes(): void
    {
        $karteChar = Project::query()->where('code', '4')->firstOrFail();
        $other = IncomeCategory::query()->where('code', 'other')->first()
            ?? IncomeCategory::query()->where('code', 'general')->firstOrFail();
        $rental = IncomeCategory::query()->where('code', 'rental')->first() ?? $other;

        Income::query()->create(array_merge(FinanceDefaults::incomeAttributes([
            'income_date' => now()->subWeeks(3)->toDateString(),
            'amount' => 150,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'description' => 'Temporary parking rental on Kart-e-Char courtyard.',
        ]), [
            'project_id' => $karteChar->id,
            'income_category_id' => $rental->id,
            'received_from' => 'Neighbour shopkeepers',
        ]));

        Income::query()->create(array_merge(FinanceDefaults::incomeAttributes([
            'income_date' => now()->subWeeks(5)->toDateString(),
            'amount' => 400,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'description' => 'Late installment fee — Haji Abdul Ghani Hotak.',
        ]), [
            'income_category_id' => $other->id,
            'customer_id' => Customer::query()->where('customer_no', 'C-005')->value('id'),
            'received_from' => 'Haji Abdul Ghani Hotak',
        ]));
    }

    private function seedLoans(): void
    {
        $khairKhana = Project::query()->where('code', '1')->firstOrFail();
        $darulaman = Project::query()->where('code', '3')->firstOrFail();

        $bankLoan = LoanService::create($khairKhana->id, [
            'lender_name' => 'Azizi Bank',
            'loan_date' => now()->subMonths(8)->toDateString(),
            'due_date' => now()->addMonths(10)->toDateString(),
            'principal' => 80000,
            'currency' => 'USD',
            'reference_no' => 'AZB-LN-88421',
            'notes' => 'Working capital for Khair Khana structure. Monthly informal repayments.',
        ]);

        LoanService::recordPayment($bankLoan, 15000, [
            'payment_method' => 'bank',
            'paid_at' => now()->subMonths(5)->toDateString(),
            'reference_no' => 'AZB-RP-01',
            'notes' => 'First repayment after floor 4 pour.',
        ]);

        LoanService::recordPayment($bankLoan, 10000, [
            'payment_method' => 'bank',
            'paid_at' => now()->subMonths(2)->toDateString(),
            'reference_no' => 'AZB-RP-02',
            'notes' => 'Second repayment.',
        ]);

        LoanService::create($darulaman->id, [
            'lender_name' => 'Haji Nazar Popalzai',
            'loan_date' => now()->subWeeks(6)->toDateString(),
            'due_date' => now()->addMonths(8)->toDateString(),
            'principal' => 15000,
            'currency' => 'USD',
            'reference_no' => 'PRIV-DA-01',
            'notes' => 'Short private loan for Darulaman wall and site office.',
        ]);
    }

    private function seedDistributions(): void
    {
        $karteChar = Project::query()->where('code', '4')->firstOrFail();
        $yaqeen = Partner::query()->where('partner_no', 'PTR-001')->firstOrFail();
        $rahman = Partner::query()->where('partner_no', 'PTR-002')->firstOrFail();

        foreach ([$yaqeen, $rahman] as $partner) {
            PartnerDistribution::query()->create([
                'project_id' => $karteChar->id,
                'partner_id' => $partner->id,
                'amount' => 18000,
                'amount_base' => 18000,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'type' => 'profit',
                'distributed_at' => '2025-07-10',
                'reference_no' => 'DIST-KC-1404-04',
                'notes' => 'First profit share after Kart-e-Char handover.',
            ]);
        }
    }

    private function seedAttendance(): void
    {
        $employees = Employee::query()->whereIn('employee_no', [
            'EMP-001', 'EMP-002', 'EMP-003', 'EMP-004', 'EMP-006', 'EMP-007', 'EMP-008',
        ])->get();

        $today = Carbon::today();
        $leaveDate = $today->copy()->subDays(9)->toDateString();
        $halfDayDate = $today->copy()->subDays(4)->toDateString();
        $dates = [];

        for ($daysAgo = 16; $daysAgo >= 1; $daysAgo--) {
            $day = $today->copy()->subDays($daysAgo);

            if ((int) $day->dayOfWeekIso === 5) {
                continue;
            }

            $dates[] = $day->toDateString();
        }

        foreach (array_unique($dates) as $date) {
            $weekday = (int) Carbon::parse($date)->dayOfWeekIso;

            foreach ($employees as $employee) {
                $isField = in_array($employee->employee_no, ['EMP-003', 'EMP-004', 'EMP-006', 'EMP-008'], true);
                $status = 'present';
                $checkIn = $isField ? '07:05' : '08:20';
                $checkOut = $isField ? '16:10' : '16:35';
                $notes = null;

                if ($employee->employee_no === 'EMP-008' && $weekday === 4 && $date === $leaveDate) {
                    $status = 'leave';
                    $checkIn = null;
                    $checkOut = null;
                    $notes = 'Family wedding in Logar.';
                } elseif ($employee->employee_no === 'EMP-002' && $date === $halfDayDate) {
                    $status = 'half_day';
                    $checkOut = '12:30';
                    $notes = 'Bank visit for payroll.';
                }

                Attendance::query()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $date,
                    ],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'status' => $status,
                        'notes' => $notes,
                    ],
                );
            }
        }
    }

    private function seedSalaries(): void
    {
        $employees = Employee::query()->orderBy('employee_no')->get();
        $lastMonth = now()->subMonth();
        $thisMonth = now();

        foreach ($employees as $employee) {
            $bonus = $employee->employee_no === 'EMP-001' ? 200 : 0;
            $deduction = $employee->employee_no === 'EMP-008' ? 50 : 0;

            Salary::query()->create([
                'employee_id' => $employee->id,
                'month' => (int) $lastMonth->month,
                'year' => (int) $lastMonth->year,
                'basic_salary' => $employee->basic_salary,
                'basic_salary_base' => $employee->basic_salary_base,
                'bonus' => $bonus,
                'bonus_base' => $bonus,
                'deduction' => $deduction,
                'deduction_base' => $deduction,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'payment_date' => $lastMonth->copy()->endOfMonth()->toDateString(),
                'status' => 'paid',
                'notes' => $bonus > 0
                    ? 'Site progress bonus for Khair Khana floor 6.'
                    : ($deduction > 0 ? 'Advance deducted from cash salary.' : 'Paid on time.'),
            ]);

            Salary::query()->create([
                'employee_id' => $employee->id,
                'month' => (int) $thisMonth->month,
                'year' => (int) $thisMonth->year,
                'basic_salary' => $employee->basic_salary,
                'basic_salary_base' => $employee->basic_salary_base,
                'bonus' => 0,
                'bonus_base' => 0,
                'deduction' => 0,
                'deduction_base' => 0,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'payment_date' => null,
                'status' => 'pending',
                'notes' => 'Current month — not yet paid.',
            ]);
        }
    }
}
