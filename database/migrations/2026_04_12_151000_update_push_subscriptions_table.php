<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Push subscriptions table already exists
        // This migration adds additional columns if they don't exist
        
        if (Schema::hasTable('push_subscriptions')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('push_subscriptions', 'uuid')) {
                    $table->uuid('uuid')->unique()->after('id');
                }
                if (!Schema::hasColumn('push_subscriptions', 'last_used_at')) {
                    $table->timestamp('last_used_at')->nullable()->after('preferences');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for columns that might have existed
    }
};