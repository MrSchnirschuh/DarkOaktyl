<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds transaction_id for Jexactyl compatibility while keeping payment_intent_id
     * for backward compatibility with existing DarkOaktyl code.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add transaction_id for Jexactyl compatibility
            // Both fields coexist for smooth migration
            $table->string('transaction_id')->nullable()->after('payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
