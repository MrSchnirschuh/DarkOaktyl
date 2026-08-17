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
        Schema::create('auto_scaling_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');

            // Thresholds
            $table->unsignedTinyInteger('cpu_threshold')->default(80)->comment('CPU threshold in percent');
            $table->unsignedTinyInteger('memory_threshold')->default(85)->comment('Memory threshold in percent');
            $table->unsignedTinyInteger('disk_threshold')->default(90)->comment('Disk threshold in percent');

            // Scaling rules
            $table->unsignedInteger('scale_up_step')->default(512)->comment('Memory to add when scaling up (MB)');
            $table->unsignedInteger('scale_down_step')->default(256)->comment('Memory to remove when scaling down (MB)');
            $table->unsignedInteger('min_memory')->default(512)->comment('Minimum memory limit (MB)');
            $table->unsignedInteger('max_memory')->default(8192)->comment('Maximum memory limit (MB)');
            $table->unsignedInteger('scale_up_cooldown')->default(5)->comment('Minutes between scale-up actions');
            $table->unsignedInteger('scale_down_cooldown')->default(10)->comment('Minutes between scale-down actions');

            // Status
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_scale_up_at')->nullable();
            $table->timestamp('last_scale_down_at')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->onDelete('cascade');

            $table->unique('server_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_scaling_rules');
    }
};
