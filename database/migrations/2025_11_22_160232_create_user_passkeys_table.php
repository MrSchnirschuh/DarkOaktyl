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
        Schema::create('user_passkeys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('credential_id', 255)->unique();
            $table->longText('public_key_credential');
            $table->string('attestation_type')->nullable();
            $table->string('aaguid', 36)->nullable();
            $table->json('transports')->nullable();
            $table->unsignedBigInteger('counter')->default(0);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_passkeys');
    }
};
