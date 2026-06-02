<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Guard against missing columns — JexPanel DBs may have already
     * renamed 'node' to 'node_id'.
     */
    public function up(): void
    {
        if (Schema::hasColumn('allocations', 'assigned_to')) {
            DB::statement('ALTER TABLE allocations MODIFY assigned_to INT UNSIGNED NULL');
        }
        if (Schema::hasColumn('allocations', 'node')) {
            DB::statement('ALTER TABLE allocations MODIFY node INT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('allocations', 'assigned_to')) {
            DB::statement('ALTER TABLE allocations MODIFY assigned_to INT NULL');
        }
        if (Schema::hasColumn('allocations', 'node')) {
            DB::statement('ALTER TABLE allocations MODIFY node INT NOT NULL');
        }
    }
};