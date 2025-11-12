<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_themes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('primary_color', 8)->default('#2563eb');
            $table->string('secondary_color', 8)->default('#1e40af');
            $table->string('accent_color', 8)->default('#f97316');
            $table->string('background_color', 8)->default('#0f172a');
            $table->string('body_color', 8)->default('#ffffff');
            $table->string('text_color', 8)->default('#0f172a');
            $table->string('muted_text_color', 8)->default('#475569');
            $table->string('button_color', 8)->default('#2563eb');
            $table->string('button_text_color', 8)->default('#ffffff');
            $table->string('logo_url')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_themes');
    }
};
