<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServiceOptions extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_options')) {
            return;
        }

        Schema::table('service_options', function (Blueprint $table) {
            if (Schema::hasColumn('service_options', 'parent_service')) {
                $table->integer('parent_service', false, true)->change();
                $table->foreign('parent_service')->references('id')->on('services');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_options')) {
            return;
        }

        Schema::table('service_options', function (Blueprint $table) {
            if (Schema::hasColumn('service_options', 'parent_service')) {
                $table->dropForeign(['parent_service']);
                $table->dropIndex(['parent_service']);
                $table->mediumInteger('parent_service', false, true)->change();
            }
        });
    }
}