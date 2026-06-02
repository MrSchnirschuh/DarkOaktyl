<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Jexpanel DBs may have already renamed 'location' to 'location_id'.
     */
    public function up(): void
    {
        if (Schema::hasColumn('nodes', 'location')) {
            DB::statement('ALTER TABLE nodes MODIFY location INT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('nodes', 'location')) {
            DB::statement('ALTER TABLE nodes MODIFY location INT NOT NULL');
        }
    }
};