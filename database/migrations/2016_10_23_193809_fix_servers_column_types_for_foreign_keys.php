<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Only alter columns that actually exist — the old column names
     * (node, owner, allocation, service, option) may have already been
     * renamed to node_id, owner_id, etc. by JexPanel migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('servers', 'node')) {
            DB::statement('ALTER TABLE servers MODIFY node INT UNSIGNED');
        }
        if (Schema::hasColumn('servers', 'owner')) {
            DB::statement('ALTER TABLE servers MODIFY owner INT UNSIGNED');
        }
        if (Schema::hasColumn('servers', 'allocation')) {
            DB::statement('ALTER TABLE servers MODIFY allocation INT UNSIGNED');
        }
        if (Schema::hasColumn('servers', 'service')) {
            DB::statement('ALTER TABLE servers MODIFY service INT UNSIGNED');
        }
        if (Schema::hasColumn('servers', 'option')) {
            DB::statement('ALTER TABLE servers MODIFY `option` INT UNSIGNED');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('servers', 'node')) {
            DB::statement('ALTER TABLE servers MODIFY node INT');
        }
        if (Schema::hasColumn('servers', 'owner')) {
            DB::statement('ALTER TABLE servers MODIFY owner INT');
        }
        if (Schema::hasColumn('servers', 'allocation')) {
            DB::statement('ALTER TABLE servers MODIFY allocation INT');
        }
        if (Schema::hasColumn('servers', 'service')) {
            DB::statement('ALTER TABLE servers MODIFY service INT');
        }
        if (Schema::hasColumn('servers', 'option')) {
            DB::statement('ALTER TABLE servers MODIFY `option` INT');
        }
    }
};