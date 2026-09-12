<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_admin_dashboard_includes_chart_series_for_year_preset(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard', ['preset' => 'year']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('mode', 'admin')
                ->where('preset', 'year')
                ->has('stats.charts.monthly.labels')
                ->has('stats.charts.monthly.sales')
                ->has('stats.charts.monthly.expenses')
                ->has('stats.charts.expenses_by_currency')
                ->has('stats.charts.projects.labels')
                ->has('stats.charts.projects.profit')
                ->has('stats.charts.projects.revenue')
                ->has('stats.kpis.period_sales')
                ->has('stats.kpis.period_expenses')
                ->has('stats.kpis.net_profit_period')
                ->has('stats.kpis.period_receipts')
                ->has('stats.kpis.sales_by_currency')
                ->has('stats.kpis.expenses_by_currency')
                ->has('stats.kpis.profit_by_currency')
                ->has('stats.kpis.receipts_by_currency')
            );
    }

    public function test_admin_dashboard_supports_all_time_preset(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard', ['preset' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('mode', 'admin')
                ->where('preset', 'all')
                ->has('stats.charts.monthly.labels')
                ->has('stats.period.from')
                ->has('stats.period.to')
            );
    }

    public function test_authenticated_users_can_visit_dashboard_v2(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard.v2'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DashboardV2')
                ->where('mode', 'admin')
                ->has('stats.kpis')
            );
    }
}
