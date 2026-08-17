<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ServiceVariablesToEggVariablesConversion extends Migration
{
    /**
     * Run the migrations.
     * Jexpanel DBs have already done this conversion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_variables')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::rename('service_variables', 'egg_variables');

        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['variable_id']);
            $table->foreign('variable_id')->references('id')->on('egg_variables')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('egg_variables')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::rename('egg_variables', 'service_variables');

        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['variable_id']);
            $table->foreign('variable_id')->references('id')->on('service_variables')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }
}
