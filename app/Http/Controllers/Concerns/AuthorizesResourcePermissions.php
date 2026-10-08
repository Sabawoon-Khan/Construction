<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Routing\Controllers\Middleware;

trait AuthorizesResourcePermissions
{
    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        $module = static::$permissionModule;

        return [
            new Middleware('permission:'.$module.'.view', only: ['index', 'show', 'export']),
            new Middleware('permission:'.$module.'.create', only: ['create', 'store']),
            new Middleware('permission:'.$module.'.edit', only: ['edit', 'update', 'move']),
            new Middleware('permission:'.$module.'.delete', only: ['destroy']),
        ];
    }
}
