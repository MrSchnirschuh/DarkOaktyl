<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('role')->default('member'); // owner, admin, member
            $table->foreignId('invited_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, declined, expired
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};