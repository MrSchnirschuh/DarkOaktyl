<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * DarkOaktyl-Anpassung:
     * - Normalisiert bestehende state-Werte
     * - Setzt NULL/'' auf 'active'
     * - Erhält DarkOaktyl-spezifische State-Werte (nicht nur active/suspended)
     *   DarkOaktyl verwendet VARCHAR statt ENUM für Flexibilität
     */
    public function up(): void
    {
        // Normalisiere NULL/empty values zu 'active'
        DB::table('users')
            ->whereNull('state')
            ->orWhere('state', '=', '')
            ->update(['state' => 'active']);

        // Sichere normalisierung: Setze alle anderen NULLs
        DB::statement("UPDATE `users` SET `state` = 'active' WHERE `state` IS NULL OR `state` = ''");
    }

    /**
     * Reverse the migrations.
     *
     * Hinweis: Diese Migration ist absichtlich irreversibel
     * da die ursprünglichen NULL-Werte nicht wiederhergestellt werden können.
     */
    public function down(): void
    {
        // Kein Rollback möglich
        // Die Daten wurden permanent normalisiert
    }
};
