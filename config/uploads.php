<?php

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\FieldReport;
use App\Models\Income;
use App\Models\Material;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;

return [

    /*
    |--------------------------------------------------------------------------
    | Chunked uploads
    |--------------------------------------------------------------------------
    |
    | Resumable large-file uploads (hundreds of MB to multi-GB) via small
    | chunks so PHP / proxy body limits never need to equal the full file size.
    |
    */

    'max_bytes' => (int) env('UPLOAD_MAX_BYTES', 5 * 1024 * 1024 * 1024), // 5 GiB

    'chunk_bytes' => (int) env('UPLOAD_CHUNK_BYTES', 5 * 1024 * 1024), // 5 MiB

    'min_chunk_bytes' => (int) env('UPLOAD_MIN_CHUNK_BYTES', 256 * 1024), // 256 KiB

    'max_chunk_bytes' => (int) env('UPLOAD_MAX_CHUNK_BYTES', 16 * 1024 * 1024), // 16 MiB

    'max_chunks' => (int) env('UPLOAD_MAX_CHUNKS', 20_000),

    'concurrency' => (int) env('UPLOAD_CONCURRENCY', 3),

    'session_ttl_hours' => (int) env('UPLOAD_SESSION_TTL_HOURS', 48),

    'max_active_sessions_per_user' => (int) env('UPLOAD_MAX_ACTIVE_SESSIONS', 10),

    'disk' => env('UPLOAD_CHUNK_DISK', 'local'),

    'chunk_directory' => env('UPLOAD_CHUNK_DIRECTORY', 'chunked-uploads'),

    /*
    |--------------------------------------------------------------------------
    | Integrity
    |--------------------------------------------------------------------------
    */

    'require_chunk_checksum' => (bool) env('UPLOAD_REQUIRE_CHUNK_CHECKSUM', true),

    'require_file_checksum' => (bool) env('UPLOAD_REQUIRE_FILE_CHECKSUM', false),

    'checksum_algo' => 'sha256',

    /*
    |--------------------------------------------------------------------------
    | Safety
    |--------------------------------------------------------------------------
    |
    | Empty allowed_extensions = any extension except the blocked list.
    | Empty allowed_mimes = any sniffed MIME except application/x-httpd-php
    | and other executable MIME types listed under blocked_mimes.
    |
    */

    'blocked_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'zsh', 'exe', 'bat', 'cmd', 'com',
        'scr', 'dll', 'so', 'dylib', 'jsp', 'asp', 'aspx', 'htaccess', 'htpasswd',
        'shtml', 'pht',
    ],

    'blocked_mimes' => [
        'application/x-httpd-php',
        'application/x-php',
        'text/x-php',
        'application/x-executable',
        'application/x-msdownload',
        'application/x-sh',
        'application/x-csh',
    ],

    'allowed_extensions' => array_filter(array_map(
        'trim',
        explode(',', (string) env('UPLOAD_ALLOWED_EXTENSIONS', '')),
    )),

    'allowed_mimes' => array_filter(array_map(
        'trim',
        explode(',', (string) env('UPLOAD_ALLOWED_MIMES', '')),
    )),

    /*
    |--------------------------------------------------------------------------
    | Free disk headroom required before starting an upload (bytes)
    |--------------------------------------------------------------------------
    */

    'min_free_bytes' => (int) env('UPLOAD_MIN_FREE_BYTES', 512 * 1024 * 1024), // 512 MiB

    /*
    |--------------------------------------------------------------------------
    | Attachable parents for chunked attachment destination
    |--------------------------------------------------------------------------
    |
    | Keys are stable resource names used by the FileUploadField component.
    | Models must use App\Concerns\HasAttachments.
    |
    */

    'attachables' => [
        'field_reports' => [
            'model' => FieldReport::class,
            'user_scoped' => true,
            'permissions' => ['field_reports.create', 'field_reports.edit'],
        ],
        'projects' => [
            'model' => Project::class,
            'user_scoped' => false,
            'permissions' => ['projects.create', 'projects.edit'],
        ],
        'units' => [
            'model' => Unit::class,
            'user_scoped' => false,
            'permissions' => ['units.create', 'units.edit'],
        ],
        'customers' => [
            'model' => Customer::class,
            'user_scoped' => false,
            'permissions' => ['customers.create', 'customers.edit'],
        ],
        'partners' => [
            'model' => Partner::class,
            'user_scoped' => false,
            'permissions' => ['partners.create', 'partners.edit'],
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'user_scoped' => false,
            'permissions' => ['suppliers.create', 'suppliers.edit'],
        ],
        'materials' => [
            'model' => Material::class,
            'user_scoped' => false,
            'permissions' => ['materials.create', 'materials.edit'],
        ],
        'purchases' => [
            'model' => Purchase::class,
            'user_scoped' => false,
            'permissions' => ['purchases.create', 'purchases.edit'],
        ],
        'sales' => [
            'model' => Sale::class,
            'user_scoped' => false,
            'permissions' => ['sales.create', 'sales.edit'],
        ],
        'expenses' => [
            'model' => Expense::class,
            'user_scoped' => false,
            'permissions' => ['expenses.create', 'expenses.edit'],
        ],
        'incomes' => [
            'model' => Income::class,
            'user_scoped' => false,
            'permissions' => ['incomes.create', 'incomes.edit'],
        ],
        'payments' => [
            'model' => Payment::class,
            'user_scoped' => false,
            'permissions' => ['payments.view', 'payments.create', 'payments.edit'],
        ],
        'employees' => [
            'model' => Employee::class,
            'user_scoped' => false,
            'permissions' => ['employees.create', 'employees.edit'],
        ],
        'attendance' => [
            'model' => Attendance::class,
            'user_scoped' => false,
            'permissions' => ['attendance.create', 'attendance.edit'],
        ],
        'salaries' => [
            'model' => Salary::class,
            'user_scoped' => false,
            'permissions' => ['salaries.create', 'salaries.edit'],
        ],
    ],

];
