<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('service_options') && Schema::hasColumn('service_options', 'parent_service')) {
            DB::statement('ALTER TABLE service_options MODIFY parent_service INT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_options') && Schema::hasColumn('service_options', 'parent_service')) {
            DB::statement('ALTER TABLE service_options MODIFY parent_service INT NOT NULL');
        }
    }
};