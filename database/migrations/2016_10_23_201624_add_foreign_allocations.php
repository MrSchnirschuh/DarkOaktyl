<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAllocations extends Migration
{
    /**
     * Run the migrations.
     * Guard against missing columns — JexPanel DBs may have already
     * renamed 'node' and 'assigned_to' to 'node_id' and 'server_id'.
     */
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            if (Schema::hasColumn('allocations', 'assigned_to')) {
                $table->integer('assigned_to', false, true)->nullable()->change();
                $table->foreign('assigned_to')->references('id')->on('servers');
            }
            if (Schema::hasColumn('allocations', 'node')) {
                $table->integer('node', false, true)->nullable(false)->change();
                $table->foreign('node')->references('id')->on('nodes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            if (Schema::hasColumn('allocations', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropIndex(['assigned_to']);
                $table->mediumInteger('assigned_to', false, true)->nullable()->change();
            }
            if (Schema::hasColumn('allocations', 'node')) {
                $table->dropForeign(['node']);
                $table->dropIndex(['node']);
                $table->mediumInteger('node', false, true)->nullable(false)->change();
            }
        });

        if (Schema::hasColumn('allocations', 'assigned_to') || Schema::hasColumn('allocations', 'node')) {
            DB::statement('ALTER TABLE allocations
                 MODIFY COLUMN assigned_to MEDIUMINT(8) UNSIGNED NULL,
                 MODIFY COLUMN node MEDIUMINT(8) UNSIGNED NOT NULL
             ');
        }
    }
}
