<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecordAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_customer_create_stores_attachments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Owner');

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'customer_no' => 'C-100',
                'name' => 'Ahmad',
                'documents' => [UploadedFile::fake()->create('id.pdf', 120, 'application/pdf')],
            ])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::query()->first();
        $this->assertNotNull($customer);
        $this->assertCount(1, $customer->attachments);
        $this->assertSame('id.pdf', $customer->attachments->first()->original_filename);
        Storage::disk('local')->assertExists($customer->attachments->first()->file_path);
    }

    public function test_user_with_view_permission_can_download_customer_attachment(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $this->actingAs($owner)
            ->post(route('customers.store'), [
                'customer_no' => 'C-101',
                'name' => 'Fatima',
                'documents' => [UploadedFile::fake()->create('passport.pdf', 80, 'application/pdf')],
            ])
            ->assertRedirect();

        $attachment = Customer::query()->first()?->attachments()->first();
        $this->assertNotNull($attachment);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('customers.view'));

        $this->actingAs($viewer)
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_download_customer_attachment(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $this->actingAs($owner)
            ->post(route('customers.store'), [
                'customer_no' => 'C-102',
                'name' => 'Omar',
                'documents' => [UploadedFile::fake()->create('note.pdf', 40, 'application/pdf')],
            ])
            ->assertRedirect();

        $attachment = Customer::query()->first()?->attachments()->first();
        $this->assertNotNull($attachment);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }
}
