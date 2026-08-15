<?php

namespace Tests\Feature\Settings;

use App\Models\Role;
use App\Models\User;
use App\Services\MisPermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(MisPermissionRegistrar::class)->sync();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_role_permissions_can_be_updated(): void
    {
        $user = $this->makeEditor();

        $role = Role::findOrCreate('Custom Role');
        $role->syncPermissions(['dashboard.view']);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.roles.index'))
            ->patch(route('settings.roles.update', $role), [
                'name' => 'Custom Role',
                'permissions' => ['dashboard.view', 'projects.view'],
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('settings.roles.index'));

        $role->refresh();
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'projects.view'],
            $role->permissions->pluck('name')->all(),
        );
    }

    public function test_role_name_can_be_updated(): void
    {
        $user = $this->makeEditor();

        $role = Role::findOrCreate('Old Name');
        $role->syncPermissions(['dashboard.view']);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.roles.index'))
            ->patch(route('settings.roles.update', $role), [
                'name' => 'New Name',
                'permissions' => ['dashboard.view'],
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('settings.roles.index'));
        $this->assertSame('New Name', $role->fresh()->name);
    }

    public function test_owner_role_cannot_be_renamed(): void
    {
        $user = $this->makeEditor();

        $role = Role::findOrCreate('Owner');
        $role->syncPermissions(Permission::all());

        $response = $this
            ->actingAs($user)
            ->from(route('settings.roles.index'))
            ->patch(route('settings.roles.update', $role), [
                'name' => 'Super Owner',
                'permissions' => ['dashboard.view'],
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('Owner', $role->fresh()->name);
    }

    private function makeEditor(): User
    {
        $user = User::query()->create([
            'name' => 'Role Editor',
            'email' => 'role-editor@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo(['roles.view', 'roles.edit']);

        return $user;
    }
}
