<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_passkeys', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->text('credential_id');
            $table->text('public_key');
            $table->text('attestation_data')->nullable();
            $table->json('transports')->nullable();
            $table->string('type')->default('public-key');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        Schema::create('user_passkey_recovery_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('token', 64);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_login_method', 20)->default('password')->after('totp_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auth_login_method');
        });
        Schema::dropIfExists('user_passkey_recovery_tokens');
        Schema::dropIfExists('user_passkeys');
    }
};
