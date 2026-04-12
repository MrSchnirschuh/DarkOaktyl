<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->constrained('server_template_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // minecraft, valheim, cs2, etc.
            $table->string('image')->nullable(); // URL or path to template image
            $table->foreignId('egg_id')->constrained('eggs');
            $table->foreignId('nest_id')->constrained('nests');
            $table->text('startup_command')->nullable(); // Custom startup command
            $table->string('docker_image')->nullable(); // Optional custom Docker image
            $table->unsignedBigInteger('default_memory')->default(1024); // MB
            $table->unsignedBigInteger('default_swap')->default(0); // MB
            $table->unsignedBigInteger('default_disk')->default(5000); // MB
            $table->unsignedBigInteger('default_cpu')->default(100); // Percent
            $table->unsignedBigInteger('default_io')->default(500);
            $table->json('environment_variables')->nullable(); // Pre-configured env vars
            $table->json('feature_limits')->nullable(); // Default feature limits
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('install_script')->nullable(); // Custom install script
            $table->longText('pre_install_script')->nullable(); // Script to run before install
            $table->longText('post_install_script')->nullable(); // Script to run after install
            $table->timestamps();

            $table->index('category_id');
            $table->index('egg_id');
            $table->index('nest_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_templates');
    }
};
