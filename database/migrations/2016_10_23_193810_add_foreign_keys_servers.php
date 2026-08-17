<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysServers extends Migration
{
    /**
     * Run the migrations.
     * Only touch columns that still exist — on JexPanel-derived DBs
     * these have already been renamed to node_id, owner_id, etc.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'node')) {
                $table->integer('node', false, true)->change();
                $table->foreign('node')->references('id')->on('nodes');
            }
            if (Schema::hasColumn('servers', 'owner')) {
                $table->integer('owner', false, true)->change();
                $table->foreign('owner')->references('id')->on('users');
            }
            if (Schema::hasColumn('servers', 'allocation')) {
                $table->integer('allocation', false, true)->change();
                $table->foreign('allocation')->references('id')->on('allocations');
            }
            if (Schema::hasColumn('servers', 'service')) {
                $table->integer('service', false, true)->change();
                $table->foreign('service')->references('id')->on('services');
            }
            if (Schema::hasColumn('servers', 'option')) {
                $table->integer('option', false, true)->change();
                $table->foreign('option')->references('id')->on('service_options');
            }

            if (!Schema::hasColumn('servers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'node')) {
                $table->dropForeign(['node']);
                $table->dropIndex(['node']);
                $table->mediumInteger('node', false, true)->change();
            }
            if (Schema::hasColumn('servers', 'owner')) {
                $table->dropForeign(['owner']);
                $table->dropIndex(['owner']);
                $table->mediumInteger('owner', false, true)->change();
            }
            if (Schema::hasColumn('servers', 'allocation')) {
                $table->dropForeign(['allocation']);
                $table->dropIndex(['allocation']);
                $table->mediumInteger('allocation', false, true)->change();
            }
            if (Schema::hasColumn('servers', 'service')) {
                $table->dropForeign(['service']);
                $table->dropIndex(['service']);
                $table->mediumInteger('service', false, true)->change();
            }
            if (Schema::hasColumn('servers', 'option')) {
                $table->dropForeign(['option']);
                $table->dropIndex(['option']);
                $table->mediumInteger('option', false, true)->change();
            }

            if (Schema::hasColumn('servers', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
}
