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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Unique identifier for the template (e.g., welcome, password_reset)');
            $table->string('name')->comment('Human-readable name');
            $table->string('subject')->comment('Email subject line');
            $table->text('body_html')->comment('HTML email body');
            $table->text('body_text')->nullable()->comment('Plain text email body');
            $table->json('variables')->nullable()->comment('Available template variables');
            $table->boolean('enabled')->default(true)->comment('Whether this template is active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
