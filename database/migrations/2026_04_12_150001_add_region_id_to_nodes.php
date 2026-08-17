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
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->constrained('regions')
                ->nullOnDelete()
                ->after('uuid');

            $table->index('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropIndex(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
