<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    protected array $tables = [
        'users',
        'attachments',
        'org_settings',
        'backup_records',
        'upload_sessions',
        'sync_devices',
        'sync_client_mutations',
        'ai_usage_logs',
        'email_logs',
        'authentication_logs',
        'status_change_logs',
        'roles',
        'agent_conversations',
        'agent_conversation_messages',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'deleted_at')) {
                    $blueprint->softDeletes();
                }
            });
        }

        if (Schema::hasTable('org_settings') && $this->hasIndex('org_settings', 'org_settings_key_unique')) {
            Schema::table('org_settings', function (Blueprint $table): void {
                $table->dropUnique(['key']);
            });

            if (! $this->hasIndex('org_settings', 'org_settings_key_index')) {
                Schema::table('org_settings', function (Blueprint $table): void {
                    $table->index('key');
                });
            }
        }

        if (Schema::hasTable('lookup_items') && $this->hasIndex('lookup_items', 'lookup_items_group_name_unique')) {
            Schema::table('lookup_items', function (Blueprint $table): void {
                $table->dropUnique(['group', 'name']);
            });

            if (! $this->hasIndex('lookup_items', 'lookup_items_group_name_index')) {
                Schema::table('lookup_items', function (Blueprint $table): void {
                    $table->index(['group', 'name']);
                });
            }
        }

        if (! Schema::hasTable('file_trash_entries')) {
            Schema::create('file_trash_entries', function (Blueprint $table): void {
                $table->id();
                $table->string('root', 32);
                $table->string('original_path', 1000);
                $table->string('trash_path', 1000);
                $table->string('name');
                $table->boolean('is_directory')->default(false);
                $table->unsignedBigInteger('size')->nullable();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('root');
                $table->index('deleted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('file_trash_entries');

        if (Schema::hasTable('lookup_items') && $this->hasIndex('lookup_items', 'lookup_items_group_name_index')) {
            Schema::table('lookup_items', function (Blueprint $table): void {
                $table->dropIndex(['group', 'name']);
                $table->unique(['group', 'name']);
            });
        }

        if (Schema::hasTable('org_settings') && $this->hasIndex('org_settings', 'org_settings_key_index')) {
            Schema::table('org_settings', function (Blueprint $table): void {
                $table->dropIndex(['key']);
                $table->unique('key');
            });
        }

        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropSoftDeletes();
            });
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['name'] ?? null) === $indexName) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
};