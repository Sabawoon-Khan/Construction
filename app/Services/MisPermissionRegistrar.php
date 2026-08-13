<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class MisPermissionRegistrar
{
    /** @return list<string> */
    public function definedPermissionNames(): array
    {
        $names = [];

        foreach (array_keys(config('mis_permissions.modules', [])) as $module) {
            foreach (config('mis_permissions.verbs', []) as $verb) {
                $names[] = "{$module}.{$verb}";
            }

            $extra = config("mis_permissions.modules.{$module}.extra", []);

            foreach ($extra as $permission) {
                $names[] = "{$module}.{$permission}";
            }
        }

        return $names;
    }

    /** @return list<Permission> */
    public function sync(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [];

        foreach ($this->definedPermissionNames() as $name) {
            $permissions[] = Permission::findOrCreate($name);
        }

        return $permissions;
    }
}
