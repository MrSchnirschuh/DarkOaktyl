<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Add columns only if they don't already exist (safe for local/dev DBs).
        if (! Schema::hasTable('node_snapshots')) {
            // If the base table is missing, nothing to do here.
            return;
        }

        if (! Schema::hasColumn('node_snapshots', 'disk_read_bytes')) {
            Schema::table('node_snapshots', function (Blueprint $table) {
                $table->unsignedBigInteger('disk_read_bytes')->nullable();
            });
        }

        if (! Schema::hasColumn('node_snapshots', 'disk_write_bytes')) {
            Schema::table('node_snapshots', function (Blueprint $table) {
                $table->unsignedBigInteger('disk_write_bytes')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('node_snapshots')) {
            return;
        }

        // Drop columns only if they exist to avoid errors on rollback in dev DBs.
        $toDrop = [];
        if (Schema::hasColumn('node_snapshots', 'disk_read_bytes')) {
            $toDrop[] = 'disk_read_bytes';
        }
        if (Schema::hasColumn('node_snapshots', 'disk_write_bytes')) {
            $toDrop[] = 'disk_write_bytes';
        }

        if (! empty($toDrop)) {
            Schema::table('node_snapshots', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }
    }
};
