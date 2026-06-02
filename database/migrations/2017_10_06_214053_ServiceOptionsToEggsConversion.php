<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ServiceOptionsToEggsConversion extends Migration
{
    /**
     * Run the migrations.
     * Jexpanel DBs have already done this conversion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_options')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('service_options', 'eggs');

        if (Schema::hasTable('packs')) {
            Schema::table('packs', function (Blueprint $table) {
                $table->dropForeign(['option_id']);
                $table->renameColumn('option_id', 'egg_id');
                $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('CASCADE');
            });
        }

        if (Schema::hasColumn('servers', 'option_id')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropForeign(['option_id']);
                $table->renameColumn('option_id', 'egg_id');
                $table->foreign('egg_id')->references('id')->on('eggs');
            });
        }

        Schema::table('eggs', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('eggs')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('eggs')->onDelete('SET NULL');
        });

        if (Schema::hasColumn('service_variables', 'option_id')) {
            Schema::table('service_variables', function (Blueprint $table) {
                $table->dropForeign(['option_id']);
                $table->renameColumn('option_id', 'egg_id');
                $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('CASCADE');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('eggs')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('eggs', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('eggs', 'service_options');

        if (Schema::hasTable('packs')) {
            Schema::table('packs', function (Blueprint $table) {
                $table->dropForeign(['egg_id']);
                $table->renameColumn('egg_id', 'option_id');
                $table->foreign('option_id')->references('id')->on('service_options')->onDelete('CASCADE');
            });
        }

        if (Schema::hasColumn('servers', 'egg_id')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropForeign(['egg_id']);
                $table->renameColumn('egg_id', 'option_id');
                $table->foreign('option_id')->references('id')->on('service_options');
            });
        }

        Schema::table('service_options', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('service_options')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('service_options')->onDelete('SET NULL');
        });

        if (Schema::hasColumn('service_variables', 'egg_id')) {
            Schema::table('service_variables', function (Blueprint $table) {
                $table->dropForeign(['egg_id']);
                $table->renameColumn('egg_id', 'option_id');
                $table->foreign('option_id')->references('id')->on('options')->onDelete('CASCADE');
            });
        }

        Schema::enableForeignKeyConstraints();
    }
}