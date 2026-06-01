<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('servers', 'organization_id')) {
            return;
        }

        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
        });

        if (!Schema::hasColumn('servers', 'monthly_cost')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->decimal('monthly_cost', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('servers', 'organization_id')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
        if (Schema::hasColumn('servers', 'monthly_cost')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('monthly_cost');
            });
        }
    }
};