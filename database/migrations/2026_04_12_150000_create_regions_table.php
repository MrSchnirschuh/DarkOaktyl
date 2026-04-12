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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 10)->unique(); // e.g., 'eu-west', 'us-east', 'asia-singapore'
            $table->string('display_name'); // e.g., 'EU West (Frankfurt)'
            $table->text('description')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->json('coordinates')->nullable(); // lat/lng for map display
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('ping_endpoint')->nullable(); // URL for latency checks
            $table->timestamps();
            
            // Only one default region
            $table->unique(['is_default'], 'unique_default_region')->where('is_default', true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
