<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auto_scaling_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('auto_scaling_rule_id');

            // Action type
            $table->enum('action', ['scale_up', 'scale_down', 'no_action'])->default('no_action');

            // Resource type that triggered scaling
            $table->enum('triggered_by', ['cpu', 'ram', 'disk', 'manual'])->nullable();

            // Metrics before scaling
            $table->unsignedTinyInteger('cpu_usage_before')->nullable();
            $table->unsignedInteger('ram_usage_mb_before')->nullable();
            $table->unsignedInteger('disk_usage_mb_before')->nullable();

            // Metrics after scaling
            $table->unsignedTinyInteger('cpu_usage_after')->nullable();
            $table->unsignedInteger('ram_usage_mb_after')->nullable();
            $table->unsignedInteger('disk_usage_after')->nullable();

            // Resource limits before/after
            $table->unsignedInteger('memory_limit_before')->nullable();
            $table->unsignedInteger('memory_limit_after')->nullable();
            $table->unsignedInteger('cpu_limit_before')->nullable();
            $table->unsignedInteger('cpu_limit_after')->nullable();
            $table->unsignedInteger('disk_limit_before')->nullable();
            $table->unsignedInteger('disk_limit_after')->nullable();

            // Reason for action or failure
            $table->text('reason')->nullable();

            // Status
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');

            // Error message if failed
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->onDelete('cascade');

            $table->foreign('auto_scaling_rule_id')
                ->references('id')
                ->on('auto_scaling_rules')
                ->onDelete('cascade');

            // Indexes for faster queries
            $table->index('server_id');
            $table->index('auto_scaling_rule_id');
            $table->index('action');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_scaling_history');
    }
};