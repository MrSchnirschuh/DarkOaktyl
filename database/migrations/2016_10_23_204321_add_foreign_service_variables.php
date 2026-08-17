<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServiceVariables extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_options') || !Schema::hasTable('service_variables')) {
            return;
        }

        Schema::table('service_variables', function (Blueprint $table) {
            $table->integer('option_id', false, true)->change();
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_variables')) {
            return;
        }

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->dropIndex(['option_id']);
            $table->mediumInteger('option_id', false, true)->change();
        });
    }
}
