<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\ShareholderProfitService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MisContractModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => $role,
            'email' => $email,
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_includes_revenue_and_salary_kpis(): void
    {
        $user = $this->makeUser('admin-kpi@example.com', 'Admin');

        $this->actingAs($user)
            ->get('/dashboard?preset=today')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('preset', 'today')
                ->has('stats.kpis.period_revenue')
                ->has('stats.kpis.period_salaries')
            );
    }

    public function test_unit_stores_cost_price_and_type(): void
    {
        $user = $this->makeUser('admin-units@example.com', 'Admin');

        $project = Project::query()->create([
            'code' => 'P-1',
            'name' => 'Site A',
            'type' => 'apartment',
            'status' => 'under_construction',
            'estimated_budget' => 0,
            'estimated_revenue' => 0,
            'progress_percent' => 0,
        ]);

        $this->actingAs($user)
            ->post('/units', [
                'project_id' => $project->id,
                'unit_no' => 'A-101',
                'type' => 'villa',
                'floor_number' => 1,
                'area' => 120,
                'bedrooms' => 3,
                'cost_price' => 40000,
                'list_price' => 55000,
                'currency' => 'USD',
                'status' => 'available',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('units', [
            'unit_no' => 'A-101',
            'type' => 'villa',
            'cost_price' => 40000,
            'list_price' => 55000,
        ]);
    }

    public function test_profit_breakdown_separates_salaries(): void
    {
        ExpenseCategory::ensureDefaults();
        $salaryId = ExpenseCategory::query()->where('code', 'salaries')->value('id');

        $this->assertNotEmpty($salaryId);

        $expense = Expense::query()->create([
            'expense_category_id' => $salaryId,
            'expense_no' => 'EXP-SAL',
            'expense_date' => now()->toDateString(),
            'amount' => 200,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
            'description' => 'August payroll',
        ]);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 200]);
        $this->assertContains((int) $expense->expense_category_id, array_map('intval', ExpenseCategory::salaryIds()));
        $this->assertEquals(200, ShareholderProfitService::salaryTotal());
        $breakdown = ShareholderProfitService::breakdown(now()->startOfMonth(), now()->endOfDay());

        $this->assertEquals(200, $breakdown['salary_total']);
        $this->assertEquals(-200, $breakdown['net_profit']);
    }

    public function test_reports_export_and_print(): void
    {
        $user = $this->makeUser('finance@example.com', 'Finance');

        $this->actingAs($user)->get('/reports?preset=month')->assertOk();
        $this->actingAs($user)->get('/reports/export?format=xlsx&preset=month')->assertOk();
        $this->actingAs($user)->get('/reports/print?preset=month')->assertOk();
    }

    public function test_shareholder_cannot_open_expenses_and_only_sees_own_partner(): void
    {
        $shareholderUser = $this->makeUser('shareholder@example.com', 'Shareholder');

        $mine = Partner::query()->create([
            'user_id' => $shareholderUser->id,
            'partner_no' => 'PTR-MINE',
            'name' => 'Mine',
        ]);
        Partner::query()->create([
            'partner_no' => 'PTR-OTHER',
            'name' => 'Other',
        ]);

        $this->actingAs($shareholderUser)->get('/expenses/create')->assertForbidden();
        $this->actingAs($shareholderUser)
            ->get('/partners')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('items.data', fn ($rows) => collect($rows)->pluck('id')->all() === [$mine->id])
            );
    }

    public function test_seeded_contract_roles_exist(): void
    {
        foreach (['Owner', 'Admin', 'Project Manager', 'Finance', 'Shareholder', 'Limited Employee'] as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }
    }
}
