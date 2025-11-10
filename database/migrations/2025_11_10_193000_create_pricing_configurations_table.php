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
        Schema::create('pricing_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->comment('Configuration name for admin reference');
            $table->boolean('enabled')->default(true);
            
            // Base prices per resource unit
            $table->decimal('cpu_price', 10, 4)->default(0)->comment('Price per 1% CPU');
            $table->decimal('memory_price', 10, 6)->default(0)->comment('Price per MB memory');
            $table->decimal('disk_price', 10, 6)->default(0)->comment('Price per MB disk');
            $table->decimal('backup_price', 10, 4)->default(0)->comment('Price per backup slot');
            $table->decimal('database_price', 10, 4)->default(0)->comment('Price per database');
            $table->decimal('allocation_price', 10, 4)->default(0)->comment('Price per allocation port');
            
            // Scaling factors for different package sizes
            $table->decimal('small_package_factor', 8, 4)->default(1.0)->comment('Multiplier for small packages (< threshold)');
            $table->decimal('medium_package_factor', 8, 4)->default(1.0)->comment('Multiplier for medium packages');
            $table->decimal('large_package_factor', 8, 4)->default(1.0)->comment('Multiplier for large packages (> threshold)');
            
            // Thresholds for package size categories (in MB of memory)
            $table->integer('small_package_threshold')->default(2048)->comment('Memory threshold for small packages in MB');
            $table->integer('large_package_threshold')->default(8192)->comment('Memory threshold for large packages in MB');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_configurations');
    }
};
