<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColumns extends Migration
{
    /**
     * Run the migrations.
     * JexPanel DBs may already have renamed columns.
     */
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $hasNode = Schema::hasColumn('allocations', 'node');
            $hasAssigned = Schema::hasColumn('allocations', 'assigned_to');

            if ($hasNode) {
                $table->dropForeign(['node']);
                $table->renameColumn('node', 'node_id');
                $table->foreign('node_id')->references('id')->on('nodes');
            }
            if ($hasAssigned) {
                $table->dropForeign(['assigned_to']);
                $table->renameColumn('assigned_to', 'server_id');
                $table->foreign('server_id')->references('id')->on('servers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $hasNodeId = Schema::hasColumn('allocations', 'node_id');
            $hasServerId = Schema::hasColumn('allocations', 'server_id');

            if ($hasNodeId) {
                $table->dropForeign(['node_id']);
                $table->dropIndex(['node_id']);
                $table->renameColumn('node_id', 'node');
                $table->foreign('node')->references('id')->on('nodes');
            }
            if ($hasServerId) {
                $table->dropForeign(['server_id']);
                $table->dropIndex(['server_id']);
                $table->renameColumn('server_id', 'assigned_to');
                $table->foreign('assigned_to')->references('id')->on('servers');
            }
        });
    }
}