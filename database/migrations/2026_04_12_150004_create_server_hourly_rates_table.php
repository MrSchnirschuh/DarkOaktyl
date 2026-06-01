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
        Schema::create('server_hourly_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->decimal('hourly_rate', 10, 4);
            $table->decimal('memory_rate', 10, 6)->nullable();
            $table->decimal('cpu_rate', 10, 6)->nullable();
            $table->decimal('disk_rate', 10, 6)->nullable();
            $table->json('pricing_breakdown')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('server_hourly_rates');
    }
};