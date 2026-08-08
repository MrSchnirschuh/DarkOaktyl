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
        Schema::create('auto_scaling_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->unsignedBigInteger('auto_scaling_rule_id');

            // Action type (scale_up | scale_down | skipped | no_action)
            $table->enum('action', ['scale_up', 'scale_down', 'skipped', 'no_action'])->default('no_action');

            // Metrics at decision time
            $table->float('cpu_percent')->nullable();
            $table->float('memory_percent')->nullable();
            $table->float('disk_percent')->nullable();

            // Memory change
            $table->unsignedInteger('old_memory')->nullable();
            $table->unsignedInteger('new_memory')->nullable();

            // Context
            $table->string('triggered_by')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['completed', 'failed', 'pending', 'success'])->default('completed');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();

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
        Schema::dropIfExists('auto_scaling_histories');
    }
};
