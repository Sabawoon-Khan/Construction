<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Standard permission verbs
    |--------------------------------------------------------------------------
    |
    | Every module below receives {module}.{verb} permissions automatically.
    | Add a new module to "modules" when you introduce a new app section.
    |
    */

    'verbs' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application modules
    |--------------------------------------------------------------------------
    |
    | Each key becomes a permission group on /settings/roles.
    | Use "extra" for module-specific permissions beyond the standard verbs.
    |
    */

    'modules' => [
        'dashboard' => [
            'extra' => [],
        ],
        'users' => [
            'extra' => [],
        ],
        'roles' => [
            'extra' => [],
        ],
        'settings' => [
            'extra' => [
                'manage',
            ],
        ],
        'backups' => [
            'extra' => [
                'run',
                'download',
                'restore',
            ],
        ],
        'storage' => [
            'extra' => [],
        ],
        'audit' => [
            'extra' => [],
        ],
        'trash' => [
            'extra' => [
                'restore',
            ],
        ],
        'field_reports' => [
            'extra' => [],
        ],
        'field_notes' => [
            'extra' => [],
        ],
        'companies' => [
            'extra' => [],
        ],
        'projects' => [
            'extra' => [],
        ],
        'units' => [
            'extra' => [],
        ],
        'customers' => [
            'extra' => [],
        ],
        'partners' => [
            'extra' => [],
        ],
        'suppliers' => [
            'extra' => [],
        ],
        'contractors' => [
            'extra' => [],
        ],
        'expense_categories' => [
            'extra' => [],
        ],
        'income_categories' => [
            'extra' => [],
        ],
        'bank_accounts' => [
            'extra' => [],
        ],
        'materials' => [
            'extra' => [],
        ],
        'material_units' => [
            'extra' => [],
        ],
        'expenses' => [
            'extra' => [],
        ],
        'incomes' => [
            'extra' => [],
        ],
        'sales' => [
            'extra' => [],
        ],
        'payments' => [
            'extra' => [],
        ],
        'vouchers' => [
            'extra' => [],
        ],
        'purchases' => [
            'extra' => [],
        ],
        'ai' => [
            'extra' => [
                'chat',
                'analytics',
            ],
        ],
    ],

];
