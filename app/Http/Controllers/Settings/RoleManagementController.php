<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use App\Services\MisPermissionRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Role;

class RoleManagementController extends Controller
{
    use AuthorizesMisPermissions;

    /** @var list<string> */
    private array $protectedRoles = ['Owner'];

    public function __construct(
        private MisPermissionRegistrar $permissions,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'roles.view');

        $this->permissions->sync();

        return Inertia::render('settings/Roles/Index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(['id', 'name']),
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'roles.create');

        $this->permissions->sync();

        return Inertia::render('settings/Roles/Create', [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'roles.create');

        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];

        $role = Role::findOrCreate($validated['name']);
        $role->syncPermissions($permissions);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', __('Role created.'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizePermission($request, 'roles.edit');

        $validated = $request->validated();
        $permissions = array_values(array_filter(
            $validated['permissions'] ?? [],
            fn ($permission) => is_string($permission) && $permission !== '',
        ));

        if (
            in_array($role->name, $this->protectedRoles, true)
            && $validated['name'] !== $role->name
        ) {
            return back()->withErrors(['name' => 'This role cannot be renamed.']);
        }

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role->load('permissions');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role updated (:count permissions).', ['count' => $role->permissions->count()]),
        ]);

        return redirect()->route('settings.roles.index');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizePermission($request, 'roles.delete');

        if (in_array($role->name, $this->protectedRoles, true)) {
            return back()->withErrors(['role' => 'This role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Cannot delete a role that is assigned to users.']);
        }

        $role->delete();

        return back()->with('success', __('Role deleted.'));
    }
}
