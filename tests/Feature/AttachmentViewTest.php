<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_image_attachment_opens_inline_in_the_browser(): void
    {
        Storage::fake('local');

        $user = $this->makeAdmin();
        $expense = $this->makeExpense($user);
        $attachment = $expense->storeAttachment(
            UploadedFile::fake()->image('receipt.jpg', 40, 40),
            'receipt.jpg',
            $user->id,
        );

        $response = $this->actingAs($user)
            ->get(route('attachments.download', $attachment));

        $response->assertOk();
        $this->assertStringContainsString('image/', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_project_expenses_include_attachment_urls(): void
    {
        Storage::fake('local');

        $user = $this->makeAdmin();
        $project = Project::query()->create([
            'code' => 'P-ATT',
            'name' => 'Attachment site',
            'type' => 'apartment',
            'status' => 'under_construction',
            'estimated_budget' => 0,
            'estimated_revenue' => 0,
            'progress_percent' => 0,
        ]);
        $expense = $this->makeExpense($user, $project->id);
        $attachment = $expense->storeAttachment(
            UploadedFile::fake()->image('bill.png', 20, 20),
            'bill.png',
            $user->id,
        );

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/Show')
                ->has('expenses.0.attachments', 1)
                ->where('expenses.0.attachments.0.id', $attachment->id)
                ->where('expenses.0.attachments.0.download_url', route('attachments.download', $attachment))
            );
    }

    private function makeAdmin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-attachments@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('Admin');

        return $user;
    }

    private function makeExpense(User $user, ?int $projectId = null): Expense
    {
        ExpenseCategory::ensureDefaults();

        return Expense::query()->create([
            'user_id' => $user->id,
            'project_id' => $projectId,
            'expense_category_id' => ExpenseCategory::query()->value('id'),
            'expense_no' => 'EXP-ATT-'.uniqid(),
            'expense_date' => now()->toDateString(),
            'paid_to' => 'Vendor',
            'amount' => 100,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'status' => 'approved',
            'description' => 'Site materials',
        ]);
    }
}
