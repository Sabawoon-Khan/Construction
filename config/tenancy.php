<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lean single-DB tenancy (optional)
    |--------------------------------------------------------------------------
    |
    | Not a multi-tenant SaaS product. When KIT_TENANCY=true, use BelongsToOrg
    | on domain models and set CurrentOrg from auth()->user()->org_id.
    |
    */

    'storage_prefix' => env('TENANCY_STORAGE_PREFIX', 'orgs'),

    'disk' => env('TENANCY_DISK', 'public'),

];
