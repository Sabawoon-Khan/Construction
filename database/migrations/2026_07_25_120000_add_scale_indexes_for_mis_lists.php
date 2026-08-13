<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scale-oriented indexes for high-volume ERP list filters (logs, backups, users).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $table->index(['event', 'logged_at'], 'authentication_logs_event_logged_at_index');
            $table->index(['success', 'logged_at'], 'authentication_logs_success_logged_at_index');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->index(['mailer', 'logged_at'], 'email_logs_mailer_logged_at_index');
            $table->index(['from_address'], 'email_logs_from_address_index');
        });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->index(['action', 'logged_at'], 'ai_usage_logs_action_logged_at_index');
            $table->index(['status', 'logged_at'], 'ai_usage_logs_status_logged_at_index');
            $table->index(['model_name'], 'ai_usage_logs_model_name_index');
        });

        Schema::table('backup_records', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'backup_records_status_created_at_index');
            $table->index(['type', 'status'], 'backup_records_type_status_index');
            $table->index(['trigger', 'created_at'], 'backup_records_trigger_created_at_index');
            $table->index(['created_at'], 'backup_records_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['name'], 'users_name_index');
            if (Schema::hasColumn('users', 'is_active')) {
                $table->index(['is_active', 'name'], 'users_is_active_name_index');
            }
        });

        Schema::table('field_reports', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'field_reports_user_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $table->dropIndex('authentication_logs_event_logged_at_index');
            $table->dropIndex('authentication_logs_success_logged_at_index');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex('email_logs_mailer_logged_at_index');
            $table->dropIndex('email_logs_from_address_index');
        });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropIndex('ai_usage_logs_action_logged_at_index');
            $table->dropIndex('ai_usage_logs_status_logged_at_index');
            $table->dropIndex('ai_usage_logs_model_name_index');
        });

        Schema::table('backup_records', function (Blueprint $table) {
            $table->dropIndex('backup_records_status_created_at_index');
            $table->dropIndex('backup_records_type_status_index');
            $table->dropIndex('backup_records_trigger_created_at_index');
            $table->dropIndex('backup_records_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_name_index');
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropIndex('users_is_active_name_index');
            }
        });

        Schema::table('field_reports', function (Blueprint $table) {
            $table->dropIndex('field_reports_user_id_created_at_index');
        });
    }
};
