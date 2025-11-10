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
        Schema::create('pricing_durations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_configuration_id');
            $table->integer('duration_days')->comment('Billing duration in days (e.g., 30 for monthly)');
            $table->decimal('price_factor', 8, 4)->default(1.0)->comment('Price multiplier for this duration');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            
            $table->foreign('pricing_configuration_id')
                ->references('id')
                ->on('pricing_configurations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_durations');
    }
};
