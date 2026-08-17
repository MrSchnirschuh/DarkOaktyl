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
        Schema::table('servers', function (Blueprint $table) {
            if (!Schema::hasColumn('servers', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('owner_id');
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
                $table->index('organization_id');
            }

            if (!Schema::hasColumn('servers', 'split_billing_enabled')) {
                $table->boolean('split_billing_enabled')->default(false)->after('organization_id');
                $table->index('split_billing_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropIndex(['organization_id']);
            }
            if (Schema::hasColumn('servers', 'split_billing_enabled')) {
                $table->dropIndex(['split_billing_enabled']);
                $table->dropColumn(['split_billing_enabled']);
            }
            if (Schema::hasColumn('servers', 'organization_id')) {
                $table->dropColumn(['organization_id']);
            }
        });
    }
};
