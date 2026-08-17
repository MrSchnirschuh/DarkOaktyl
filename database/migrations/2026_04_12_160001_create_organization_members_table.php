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
        if (Schema::hasTable('organization_members')) {
            return;
        }

        Schema::create('organization_members', function (Blueprint $table) {
            $table->id()->primary();
            $table->unsignedInteger('organization_id');
            $table->unsignedInteger('user_id');
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->boolean('is_active')->default(true);
            $table->decimal('split_percentage', 5, 2)->nullable(); // Percentage (0-100)
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint - one membership per user per organization
            $table->unique(['organization_id', 'user_id']);

            // Indexes
            $table->index('organization_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};
