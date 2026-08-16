<?php

use App\Models\AiConversation;
use App\Models\AiUsageLog;
use App\Models\Attachment;
use App\Models\AuthenticationLog;
use App\Models\BackupRecord;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Expense;
use App\Models\FieldNote;
use App\Models\FieldReport;
use App\Models\FileTrashEntry;
use App\Models\Income;
use App\Models\LookupItem;
use App\Models\Material;
use App\Models\OrgSetting;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StatusChangeLog;
use App\Models\Supplier;
use App\Models\SyncClientMutation;
use App\Models\SyncDevice;
use App\Models\UploadSession;
use App\Models\User;

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
            'model' => FieldReport::class,
            'label' => 'Field report',
            'title' => 'title',
        ],
        'field_notes' => [
            'model' => FieldNote::class,
            'label' => 'Field note',
            'title' => 'name',
        ],
        'lookup_items' => [
            'model' => LookupItem::class,
            'label' => 'Lookup item',
            'title' => 'name',
        ],
        'users' => [
            'model' => User::class,
            'label' => 'User',
            'title' => 'name',
        ],
        'roles' => [
            'model' => Role::class,
            'label' => 'Role',
            'title' => 'name',
        ],
        'attachments' => [
            'model' => Attachment::class,
            'label' => 'Attachment',
            'title' => 'original_filename',
        ],
        'org_settings' => [
            'model' => OrgSetting::class,
            'label' => 'Setting',
            'title' => 'key',
        ],
        'backup_records' => [
            'model' => BackupRecord::class,
            'label' => 'Backup',
            'title' => 'name',
        ],
        'upload_sessions' => [
            'model' => UploadSession::class,
            'label' => 'Upload session',
            'title' => 'original_filename',
        ],
        'sync_devices' => [
            'model' => SyncDevice::class,
            'label' => 'Sync device',
            'title' => 'name',
        ],
        'sync_client_mutations' => [
            'model' => SyncClientMutation::class,
            'label' => 'Sync mutation',
            'title' => 'resource',
        ],
        'ai_usage_logs' => [
            'model' => AiUsageLog::class,
            'label' => 'AI usage log',
            'title' => 'action',
        ],
        'email_logs' => [
            'model' => EmailLog::class,
            'label' => 'Email log',
            'title' => 'subject',
        ],
        'authentication_logs' => [
            'model' => AuthenticationLog::class,
            'label' => 'Login log',
            'title' => 'email',
        ],
        'status_change_logs' => [
            'model' => StatusChangeLog::class,
            'label' => 'Status change',
            'title' => 'to_status',
        ],
        'ai_conversations' => [
            'model' => AiConversation::class,
            'label' => 'AI conversation',
            'title' => 'title',
        ],
        'file_trash_entries' => [
            'model' => FileTrashEntry::class,
            'label' => 'File',
            'title' => 'name',
        ],
        'projects' => [
            'model' => Project::class,
            'label' => 'Project',
            'title' => 'name',
        ],
        'customers' => [
            'model' => Customer::class,
            'label' => 'Customer',
            'title' => 'name',
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'label' => 'Supplier',
            'title' => 'name',
        ],
        'materials' => [
            'model' => Material::class,
            'label' => 'Material',
            'title' => 'name',
        ],
        'purchases' => [
            'model' => Purchase::class,
            'label' => 'Purchase',
            'title' => 'purchase_no',
        ],
        'partners' => [
            'model' => Partner::class,
            'label' => 'Partner',
            'title' => 'name',
        ],
        'sales' => [
            'model' => Sale::class,
            'label' => 'Sale',
            'title' => 'sale_no',
        ],
        'expenses' => [
            'model' => Expense::class,
            'label' => 'Expense',
            'title' => 'expense_no',
        ],
        'incomes' => [
            'model' => Income::class,
            'label' => 'Income',
            'title' => 'income_no',
        ],
    ],

];
