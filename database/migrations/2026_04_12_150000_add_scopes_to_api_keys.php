<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScopesToApiKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * Fügt die 'scopes' Spalte zur api_keys Tabelle hinzu.
     * Scopes werden als JSON gespeichert: ["server:read", "server:write", ...]
     * NULL = legacy Keys haben alle Berechtigungen (Rückwärtskompatibilität).
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            // JSON-Feld für Scopes - NULL bedeutet alle Berechtigungen (legacy)
            $table->json('scopes')->nullable()->after('allowed_ips')->comment('API Key Scopes als JSON-Array. NULL = alle Berechtigungen (legacy)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });
    }
}
