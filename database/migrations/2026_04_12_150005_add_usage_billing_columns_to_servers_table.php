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
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('is_paused')->default(false)->after('status');
            $table->dateTime('paused_at')->nullable()->after('is_paused');
            $table->dateTime('resumed_at')->nullable()->after('paused_at');
            $table->enum('billing_mode', ['monthly', 'hourly'])->default('monthly')->after('billing_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['is_paused', 'paused_at', 'resumed_at', 'billing_mode']);
        });
    }
};