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
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Human-readable name for the scheduled email');
            $table->string('template_key')->comment('Key of email_templates to use');
            $table->string('trigger_type')->comment('Type of trigger: cron, date, event');
            $table->string('trigger_value')->nullable()->comment('Cron expression or date string');
            $table->string('event_name')->nullable()->comment('Event name if trigger_type is event');
            $table->json('recipients')->nullable()->comment('Recipient selection criteria');
            $table->json('template_data')->nullable()->comment('Additional data to pass to template');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
            
            $table->foreign('template_key')->references('key')->on('email_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_emails');
    }
};
