<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CascadeDeletionWhenAServerOrVariableIsDeleted extends Migration
{
    public function up(): void
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            if (Schema::hasColumn('server_variables', 'variable_id')) {
                $table->dropForeign(['variable_id']);
                $table->foreign('variable_id')->references('id')->on('service_variables')->onDelete('cascade');
            }
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            if (Schema::hasColumn('server_variables', 'variable_id')) {
                $table->dropForeign(['variable_id']);
                $table->foreign('variable_id')->references('id')->on('service_variables');
            }
            $table->foreign('server_id')->references('id')->on('servers');
        });
    }
}
