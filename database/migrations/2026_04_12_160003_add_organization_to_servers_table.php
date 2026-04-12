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
            $table->unsignedBigInteger('organization_id')->nullable()->after('owner_id');
            $table->boolean('split_billing_enabled')->default(false)->after('organization_id');

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->index('organization_id');
            $table->index('split_billing_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['split_billing_enabled']);
            $table->dropColumn(['organization_id', 'split_billing_enabled']);
        });
    }
};
