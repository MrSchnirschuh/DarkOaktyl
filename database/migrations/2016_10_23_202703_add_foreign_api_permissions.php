<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignApiPermissions extends Migration
{
    /**
     * Run the migrations.
     * Jexpanel DBs may have already dropped/restructured api_permissions.
     */
    public function up(): void
    {
        if (!Schema::hasTable('api_permissions')) {
            return;
        }

        Schema::table('api_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('api_permissions', 'key_id')) {
                $table->integer('key_id', false, true)->nullable(false)->change();
                $table->foreign('key_id')->references('id')->on('api_keys');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('api_permissions')) {
            return;
        }

        Schema::table('api_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('api_permissions', 'key_id')) {
                $table->dropForeign(['key_id']);
                $table->dropIndex(['key_id']);
                $table->mediumInteger('key_id', false, true)->nullable(false)->change();
            }
        });
    }
}
