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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'event']);
            $table->index(['webhook_id', 'success', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
