<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * DarkOaktyl-Anpassung:
     * - Fügt server_id zur orders Tabelle hinzu
     * - Foreign Key auf servers.id
     * - Wichtig: orders.id ist BIGINT UNSIGNED, servers.id ist INT UNSIGNED
     * - Typ-Mismatch beachten: Foreign Key Types müssen kompatibel sein!
     *
     * ACHTUNG: In DarkOaktyl verwendet orders.id BIGINT (autoincrement),
     * während servers.id INT UNSIGNED ist. Laravel's foreignId() erstellt BIGINT,
     * daher verwenden wir unsignedInteger() für Kompatibilität.
     */
    public function up(): void
    {
        // Prüfe ob server_id bereits existiert
        if (!Schema::hasColumn('orders', 'server_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Verwende unsignedInteger wie in der ursprünglichen servers Tabelle
                $table->unsignedInteger('server_id')->nullable()->after('product_id');
            });

            // Foreign Key separat hinzufügen (für bessere Fehlerkontrolle)
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('server_id')
                    ->references('id')
                    ->on('servers')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'server_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Drop foreign key first (Laravel generiert automatischen Namen)
                $table->dropForeign(['server_id']);
                $table->dropColumn('server_id');
            });
        }
    }
};
