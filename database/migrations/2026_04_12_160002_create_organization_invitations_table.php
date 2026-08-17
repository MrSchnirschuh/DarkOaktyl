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
        if (Schema::hasTable('organization_invitations')) {
            return;
        }

        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id()->primary();
            $table->unsignedInteger('organization_id');
            $table->string('email', 191);
            $table->string('token', 64)->unique();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->unsignedInteger('invited_by_user_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('organization_id');
            $table->index('email');
            $table->index('token');
            $table->index('expires_at');
            $table->index('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
