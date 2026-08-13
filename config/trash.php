<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trash retention
    |--------------------------------------------------------------------------
    |
    | Soft-deleted items older than this many days are permanently purged by
    | the scheduled `trash:prune` command.
    |
    */

    'retention_days' => (int) env('TRASH_RETENTION_DAYS', 60),

    /*
    |--------------------------------------------------------------------------
    | File manager trash folder
    |--------------------------------------------------------------------------
    */

    'file_trash_dirname' => '.trash',

    /*
    |--------------------------------------------------------------------------
    | Trashable model registry
    |--------------------------------------------------------------------------
    |
    | Each entry appears in Settings → Trash. Models must use SoftDeletes.
    | Optional "title" is a column or accessor used for the list label.
    |
    */

    'models' => [
        'field_reports' => [
            'model' => App\Models\FieldReport::class,
            'label' => 'Field report',
            'title' => 'title',
        ],
        'field_notes' => [
            'model' => App\Models\FieldNote::class,
            'label' => 'Field note',
            'title' => 'name',
        ],
        'lookup_items' => [
            'model' => App\Models\LookupItem::class,
            'label' => 'Lookup item',
            'title' => 'name',
        ],
        'users' => [
            'model' => App\Models\User::class,
            'label' => 'User',
            'title' => 'name',
        ],
        'roles' => [
            'model' => App\Models\Role::class,
            'label' => 'Role',
            'title' => 'name',
        ],
        'attachments' => [
            'model' => App\Models\Attachment::class,
            'label' => 'Attachment',
            'title' => 'original_filename',
        ],
        'org_settings' => [
            'model' => App\Models\OrgSetting::class,
            'label' => 'Setting',
            'title' => 'key',
        ],
        'backup_records' => [
            'model' => App\Models\BackupRecord::class,
            'label' => 'Backup',
            'title' => 'name',
        ],
        'upload_sessions' => [
            'model' => App\Models\UploadSession::class,
            'label' => 'Upload session',
            'title' => 'original_filename',
        ],
        'sync_devices' => [
            'model' => App\Models\SyncDevice::class,
            'label' => 'Sync device',
            'title' => 'name',
        ],
        'sync_client_mutations' => [
            'model' => App\Models\SyncClientMutation::class,
            'label' => 'Sync mutation',
            'title' => 'resource',
        ],
        'ai_usage_logs' => [
            'model' => App\Models\AiUsageLog::class,
            'label' => 'AI usage log',
            'title' => 'action',
        ],
        'email_logs' => [
            'model' => App\Models\EmailLog::class,
            'label' => 'Email log',
            'title' => 'subject',
        ],
        'authentication_logs' => [
            'model' => App\Models\AuthenticationLog::class,
            'label' => 'Login log',
            'title' => 'email',
        ],
        'status_change_logs' => [
            'model' => App\Models\StatusChangeLog::class,
            'label' => 'Status change',
            'title' => 'to_status',
        ],
        'ai_conversations' => [
            'model' => App\Models\AiConversation::class,
            'label' => 'AI conversation',
            'title' => 'title',
        ],
        'file_trash_entries' => [
            'model' => App\Models\FileTrashEntry::class,
            'label' => 'File',
            'title' => 'name',
        ],
        'projects' => [
            'model' => App\Models\Project::class,
            'label' => 'Project',
            'title' => 'name',
        ],
        'customers' => [
            'model' => App\Models\Customer::class,
            'label' => 'Customer',
            'title' => 'name',
        ],
        'partners' => [
            'model' => App\Models\Partner::class,
            'label' => 'Partner',
            'title' => 'name',
        ],
        'suppliers' => [
            'model' => App\Models\Supplier::class,
            'label' => 'Supplier',
            'title' => 'name',
        ],
        'contractors' => [
            'model' => App\Models\Contractor::class,
            'label' => 'Contractor',
            'title' => 'name',
        ],
        'sales' => [
            'model' => App\Models\Sale::class,
            'label' => 'Sale',
            'title' => 'sale_no',
        ],
        'expenses' => [
            'model' => App\Models\Expense::class,
            'label' => 'Expense',
            'title' => 'expense_no',
        ],
        'incomes' => [
            'model' => App\Models\Income::class,
            'label' => 'Income',
            'title' => 'income_no',
        ],
    ],

];
