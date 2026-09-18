<?php

namespace Tests\Feature;

use App\Jobs\PruneMisLogsJob;
use App\Jobs\ProcessOfflineSyncBatchJob;
use App\Jobs\RunBackupJob;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use App\Models\Role;
use Tests\TestCase;

class KitJobsAndExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_manual_backup_dispatches_run_backup_job(): void
    {
        Bus::fake([RunBackupJob::class]);

        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        $this->actingAs($owner)
            ->post(route('settings.backups.store'), ['type' => 'database'])
            ->assertRedirect();

        Bus::assertDispatched(RunBackupJob::class, function (RunBackupJob $job) use ($owner): bool {
            return $job->type === 'database'
                && $job->trigger === 'manual'
                && $job->userId === $owner->id;
        });
    }

    public function test_prune_mis_logs_job_can_be_queued(): void
    {
        Queue::fake();

        PruneMisLogsJob::dispatch(days: 60);

        Queue::assertPushed(PruneMisLogsJob::class, function (PruneMisLogsJob $job): bool {
            return $job->days === 60 && $job->dryRun === false;
        });
    }

    public function test_offline_sync_batch_job_can_be_queued(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        ProcessOfflineSyncBatchJob::dispatch($user->id, [
            [
                'client_uuid' => '11111111-1111-1111-1111-111111111111',
                'resource' => 'field_reports',
                'action' => 'create',
                'body' => ['title' => 'Queued'],
            ],
        ]);

        Queue::assertPushed(ProcessOfflineSyncBatchJob::class, function (ProcessOfflineSyncBatchJob $job) use ($user): bool {
            return $job->userId === $user->id && count($job->mutations) === 1;
        });
    }

    public function test_users_csv_and_excel_export(): void
    {
        config([
            'kit.features.exports' => true,
            'kit.features.excel_exports' => true,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        $this->actingAs($owner)
            ->get(route('settings.users.export'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $excel = $this->actingAs($owner)
            ->get(route('settings.users.export', ['format' => 'xlsx']))
            ->assertOk();

        $excel->assertHeader('content-type');
        $this->assertStringContainsString(
            'Workbook',
            $excel->streamedContent(),
        );
    }

    public function test_users_export_respects_all_vs_filtered_scope(): void
    {
        config([
            'kit.features.exports' => true,
            'kit.features.excel_exports' => true,
        ]);

        $owner = User::factory()->create(['name' => 'Owner Admin', 'email' => 'owner-export@example.com']);
        $owner->assignRole(Role::findByName('Owner'));
        User::factory()->create(['name' => 'Alpha Unique', 'email' => 'alpha-unique@example.com']);
        User::factory()->create(['name' => 'Beta Other', 'email' => 'beta-other@example.com']);

        $filtered = $this->actingAs($owner)
            ->get(route('settings.users.export', [
                'format' => 'xlsx',
                'scope' => 'filtered',
                'search' => 'Alpha Unique',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Alpha Unique', $filtered);
        $this->assertStringNotContainsString('Beta Other', $filtered);

        $all = $this->actingAs($owner)
            ->get(route('settings.users.export', [
                'format' => 'xlsx',
                'scope' => 'all',
                'search' => 'Alpha Unique',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Alpha Unique', $all);
        $this->assertStringContainsString('Beta Other', $all);
    }

    public function test_expenses_export_respects_all_vs_filtered_scope(): void
    {
        config([
            'kit.features.exports' => true,
            'kit.features.excel_exports' => true,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        ExpenseCategory::ensureDefaults();
        $categoryId = ExpenseCategory::query()->value('id');
        $this->assertNotNull($categoryId);

        Expense::query()->forceCreate([
            'user_id' => $owner->id,
            'expense_category_id' => $categoryId,
            'expense_no' => 'EXP-MATCH',
            'expense_date' => now()->toDateString(),
            'paid_to' => 'Vendor Match',
            'amount' => 100,
            'amount_base' => 100,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
            'description' => 'Matched operating cost',
        ]);

        Expense::query()->forceCreate([
            'user_id' => $owner->id,
            'expense_category_id' => $categoryId,
            'expense_no' => 'EXP-OTHER',
            'expense_date' => now()->toDateString(),
            'paid_to' => 'Vendor Other',
            'amount' => 50,
            'amount_base' => 50,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
            'description' => 'Other operating cost',
        ]);

        $filtered = $this->actingAs($owner)
            ->get(route('expenses.export', [
                'format' => 'xlsx',
                'scope' => 'filtered',
                'search' => 'Vendor Match',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Vendor Match', $filtered);
        $this->assertStringNotContainsString('Vendor Other', $filtered);

        $all = $this->actingAs($owner)
            ->get(route('expenses.export', [
                'format' => 'xlsx',
                'scope' => 'all',
                'search' => 'Vendor Match',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Vendor Match', $all);
        $this->assertStringContainsString('Vendor Other', $all);
    }

    public function test_expenses_excel_export_requires_flag(): void
    {
        config([
            'kit.features.exports' => true,
            'kit.features.excel_exports' => false,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        $this->actingAs($owner)
            ->get(route('expenses.export', ['format' => 'xlsx']))
            ->assertNotFound();
    }

    public function test_project_expenses_print_page_renders(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        $project = Project::query()->create([
            'user_id' => $owner->id,
            'code' => 'PRJ-PRINT',
            'name' => 'Print Project',
            'type' => 'apartment',
            'status' => 'planning',
        ]);

        $this->actingAs($owner)
            ->get(route('projects.expenses.print', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/ExpensesPrint')
                ->where('item.id', $project->id)
            );
    }

    public function test_users_excel_export_requires_flag(): void
    {
        config([
            'kit.features.exports' => true,
            'kit.features.excel_exports' => false,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole(Role::findByName('Owner'));

        $this->actingAs($owner)
            ->get(route('settings.users.export', ['format' => 'xlsx']))
            ->assertNotFound();
    }

    public function test_users_print_view(): void
    {
        config(['kit.features.exports' => true]);

        $owner = User::factory()->create(['name' => 'Print Owner']);
        $owner->assignRole(Role::findByName('Owner'));

        $this->actingAs($owner)
            ->get(route('settings.users.print'))
            ->assertOk()
            ->assertSee('Print Owner', false)
            ->assertSee(__('Users'), false);
    }
}
