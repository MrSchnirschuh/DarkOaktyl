<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_runtime_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->enum('status', ['running', 'paused', 'stopped', 'offline'])->default('offline');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('stopped_at')->nullable();
            $table->decimal('hours_accumulated', 10, 2)->default(0.00);
            $table->dateTime('last_billed_at')->nullable();
            $table->timestamps();

            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->onDelete('cascade');

            $table->unique('server_id');
            $table->index(['server_id', 'status']);
            $table->index('last_billed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_runtime_tracking');
    }
};