<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'global-search@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('Admin');

        return $user;
    }

    public function test_global_search_finds_customers_and_projects(): void
    {
        $user = $this->makeAdmin();

        Project::query()->create([
            'code' => 'PRJ-SEARCH',
            'name' => 'Sunrise Towers',
            'type' => 'apartment',
            'status' => 'under_construction',
            'estimated_budget' => 0,
            'estimated_revenue' => 0,
            'progress_percent' => 0,
        ]);

        Customer::query()->create([
            'customer_no' => 'C-100',
            'name' => 'Ahmad Karimi',
            'phone' => '0700000000',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/search?q=Sunrise')
            ->assertOk()
            ->assertJsonPath('query', 'Sunrise');

        $groups = $response->json('groups');
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups);

        $projectGroup = collect($groups)->firstWhere('key', 'projects');
        $this->assertNotNull($projectGroup);
        $this->assertSame('Sunrise Towers', $projectGroup['results'][0]['title']);
        $this->assertStringContainsString('/projects/', $projectGroup['results'][0]['url']);
        $this->assertSame('edit', $projectGroup['results'][0]['action']);
    }

    public function test_global_search_supports_multi_word_and_related_records(): void
    {
        $user = $this->makeAdmin();

        $project = Project::query()->create([
            'code' => 'PRJ-1',
            'name' => 'Green Valley',
            'type' => 'apartment',
            'status' => 'under_construction',
            'estimated_budget' => 0,
            'estimated_revenue' => 0,
            'progress_percent' => 0,
        ]);

        $customer = Customer::query()->create([
            'customer_no' => 'C-200',
            'name' => 'Sara Nazari',
            'phone' => '0799 111 222',
        ]);

        $unit = Unit::query()->create([
            'project_id' => $project->id,
            'unit_no' => 'A-12',
            'type' => 'apartment',
            'status' => 'available',
            'list_price' => 1000,
            'currency' => 'USD',
        ]);

        Sale::query()->create([
            'project_id' => $project->id,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'sale_no' => 'S-900',
            'status' => 'active',
            'currency' => 'USD',
            'discount' => 0,
        ]);

        $customerHits = $this->actingAs($user)
            ->getJson('/search?q=Sara Nazari')
            ->assertOk()
            ->json('groups');

        $this->assertNotNull(collect($customerHits)->firstWhere('key', 'customers'));

        $saleHits = $this->actingAs($user)
            ->getJson('/search?q=Sara')
            ->assertOk()
            ->json('groups');

        $saleGroup = collect($saleHits)->firstWhere('key', 'sales');
        $this->assertNotNull($saleGroup);
        $this->assertSame('S-900', $saleGroup['results'][0]['title']);
    }

    public function test_global_search_rejects_single_letter_non_numeric(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->getJson('/search?q=a')
            ->assertOk()
            ->assertJsonPath('groups', []);
    }

    public function test_global_search_respects_permissions(): void
    {
        $user = User::query()->create([
            'name' => 'Limited',
            'email' => 'limited-search@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Project::query()->create([
            'code' => 'PRJ-HIDDEN',
            'name' => 'Hidden Project',
            'type' => 'apartment',
            'status' => 'under_construction',
            'estimated_budget' => 0,
            'estimated_revenue' => 0,
            'progress_percent' => 0,
        ]);

        $this->actingAs($user)
            ->getJson('/search?q=Hidden')
            ->assertOk()
            ->assertJsonPath('groups', []);
    }
}
