<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_triggers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type');
            $table->string('schedule_type')->default('once');
            $table->string('event_key')->nullable();
            $table->timestampTz('schedule_at')->nullable();
            $table->string('cron_expression')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->json('payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampTz('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['trigger_type', 'is_active']);
            $table->index('event_key');
            $table->index('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_triggers');
    }
};
