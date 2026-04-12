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
        Schema::create('user_oauth_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider', 50)->index(); // 'discord', 'google'
            $table->string('provider_id', 255)->index();
            $table->string('email', 191);
            $table->json('provider_data')->nullable();
            $table->timestamps();

            // Unique constraint: one provider account per user
            $table->unique(['user_id', 'provider'], 'user_oauth_user_provider_unique');
            // Unique constraint: one provider_id per provider
            $table->unique(['provider', 'provider_id'], 'user_oauth_provider_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_oauth_accounts');
    }
};
