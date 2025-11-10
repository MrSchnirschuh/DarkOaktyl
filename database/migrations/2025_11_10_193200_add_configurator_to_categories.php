<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('pricing_configuration_id')->nullable()->after('egg_id');
            $table->boolean('use_configurator')->default(false)->after('pricing_configuration_id')
                ->comment('If true, use resource configurator instead of fixed products');
            
            $table->foreign('pricing_configuration_id')
                ->references('id')
                ->on('pricing_configurations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['pricing_configuration_id']);
            $table->dropColumn(['pricing_configuration_id', 'use_configurator']);
        });
    }
};
