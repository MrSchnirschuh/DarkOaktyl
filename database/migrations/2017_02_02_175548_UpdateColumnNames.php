<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnNames extends Migration
{
    /**
     * Run the migrations.
     * JexPanel DBs already have the renamed columns (node_id, owner_id, etc.).
     * Only strip old foreign keys and rename if the old columns still exist.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'node')) {
                $table->dropForeign(['node']);
            }
            if (Schema::hasColumn('servers', 'owner')) {
                $table->dropForeign(['owner']);
            }
            if (Schema::hasColumn('servers', 'allocation')) {
                $table->dropForeign(['allocation']);
            }
            if (Schema::hasColumn('servers', 'service')) {
                $table->dropForeign(['service']);
            }
            if (Schema::hasColumn('servers', 'option')) {
                $table->dropForeign(['option']);
            }
            if (Schema::hasColumn('servers', 'pack')) {
                $table->dropForeign(['pack']);
            }

            if (Schema::hasColumn('servers', 'node')) {
                $table->renameColumn('node', 'node_id');
            }
            if (Schema::hasColumn('servers', 'owner')) {
                $table->renameColumn('owner', 'owner_id');
            }
            if (Schema::hasColumn('servers', 'allocation')) {
                $table->renameColumn('allocation', 'allocation_id');
            }
            if (Schema::hasColumn('servers', 'service')) {
                $table->renameColumn('service', 'service_id');
            }
            if (Schema::hasColumn('servers', 'option')) {
                $table->renameColumn('option', 'option_id');
            }
            if (Schema::hasColumn('servers', 'pack')) {
                $table->renameColumn('pack', 'pack_id');
            }

            // Always ensure foreign keys exist (harmless if already there)
            try {
                $table->foreign('node_id')->references('id')->on('nodes');
            } catch (\Exception $e) {}
            try {
                $table->foreign('owner_id')->references('id')->on('users');
            } catch (\Exception $e) {}
            try {
                $table->foreign('allocation_id')->references('id')->on('allocations');
            } catch (\Exception $e) {}
            try {
                $table->foreign('service_id')->references('id')->on('services');
            } catch (\Exception $e) {}
            try {
                $table->foreign('option_id')->references('id')->on('service_options');
            } catch (\Exception $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'node_id')) {
                $table->dropForeign(['node_id', 'owner_id', 'allocation_id', 'service_id', 'option_id']);
                $table->renameColumn('node_id', 'node');
                $table->renameColumn('owner_id', 'owner');
                $table->renameColumn('allocation_id', 'allocation');
                $table->renameColumn('service_id', 'service');
                $table->renameColumn('option_id', 'option');
                if (Schema::hasColumn('servers', 'pack_id')) {
                    $table->renameColumn('pack_id', 'pack');
                }

                $table->foreign('node')->references('id')->on('nodes');
                $table->foreign('owner')->references('id')->on('users');
                $table->foreign('allocation')->references('id')->on('allocations');
                $table->foreign('service')->references('id')->on('services');
                $table->foreign('option')->references('id')->on('service_options');
                if (Schema::hasColumn('servers', 'pack')) {
                    $table->foreign('pack')->references('id')->on('service_packs');
                }
            }
        });
    }
}