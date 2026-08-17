<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServerVariables extends Migration
{
    public function up(): void
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->integer('server_id', false, true)->nullable()->change();
            if (Schema::hasColumn('server_variables', 'variable_id')) {
                $table->integer('variable_id', false, true)->nullable(false)->change();
                $table->foreign('variable_id')->references('id')->on('service_variables');
            }
            $table->foreign('server_id')->references('id')->on('servers');
        });
    }

    public function down(): void
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            if (Schema::hasColumn('server_variables', 'variable_id')) {
                $table->dropForeign(['variable_id']);
                $table->mediumInteger('variable_id', false, true)->nullable(false)->change();
            }
            $table->mediumInteger('server_id', false, true)->nullable()->change();
        });
    }
}
