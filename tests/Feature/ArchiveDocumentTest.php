<?php

namespace Tests\Feature;

use App\Models\ArchiveDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ArchiveDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_archive_create_stores_attachments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Owner');

        $this->actingAs($user)
            ->post(route('archive.store'), [
                'title' => 'Office lease',
                'category' => 'contract',
                'documents' => [UploadedFile::fake()->create('lease.pdf', 120, 'application/pdf')],
            ])
            ->assertRedirect(route('archive.index'));

        $document = ArchiveDocument::query()->first();
        $this->assertNotNull($document);
        $this->assertSame('Office lease', $document->title);
        $this->assertCount(1, $document->attachments);
        Storage::disk('local')->assertExists($document->attachments->first()->file_path);
    }

    public function test_user_with_view_permission_can_download_archive_attachment(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $this->actingAs($owner)
            ->post(route('archive.store'), [
                'title' => 'NDA',
                'category' => 'legal',
                'documents' => [UploadedFile::fake()->create('nda.pdf', 80, 'application/pdf')],
            ])
            ->assertRedirect();

        $attachment = ArchiveDocument::query()->first()?->attachments()->first();
        $this->assertNotNull($attachment);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('archive.view'));

        $this->actingAs($viewer)
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_download_archive_attachment(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $this->actingAs($owner)
            ->post(route('archive.store'), [
                'title' => 'Memo',
                'category' => 'correspondence',
                'documents' => [UploadedFile::fake()->create('memo.pdf', 40, 'application/pdf')],
            ])
            ->assertRedirect();

        $attachment = ArchiveDocument::query()->first()?->attachments()->first();
        $this->assertNotNull($attachment);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }
}
