<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['timestamp', 'event'], 'activity_logs_timestamp_event_index');
            $table->index(['is_admin', 'timestamp'], 'activity_logs_is_admin_timestamp_index');
        });

        Schema::table('activity_log_subjects', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'activity_log_subjects_subject_index');
            $table->index(['activity_log_id', 'subject_type', 'subject_id'], 'activity_log_subjects_full_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_timestamp_event_index');
            $table->dropIndex('activity_logs_is_admin_timestamp_index');
        });

        Schema::table('activity_log_subjects', function (Blueprint $table) {
            $table->dropIndex('activity_log_subjects_subject_index');
            $table->dropIndex('activity_log_subjects_full_index');
        });
    }
};
