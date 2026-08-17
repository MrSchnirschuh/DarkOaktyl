<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Foreign Key Type Consistency Check.
     *
     * DarkOaktyl-spezifische Typ-Inkonsistenzen dokumentieren und beheben:
     * - servers.id: INT UNSIGNED (increments)
     * - orders.id: BIGINT UNSIGNED (id())
     * - coupon_redemptions.order_id: BIGINT UNSIGNED (foreignId)
     * - coupon_redemptions.user_id: INT UNSIGNED (unsignedInteger)
     * - users.id: INT UNSIGNED (increments in alten Migrations)
     * - coupons.id: BIGINT UNSIGNED
     *
     * Diese Migration dokumentiert die Typ-Inkonsistenzen für zukünftige Referenz.
     */
    public function up(): void
    {
        // Tabelle zur Dokumentation von Foreign Key Typen
        // Diese Tabelle dient nur als Dokumentation und wird nicht für Referenzen genutzt
        if (!Schema::hasTable('fk_type_documentation')) {
            Schema::create('fk_type_documentation', function ($table) {
                $table->id();
                $table->string('table_name');
                $table->string('column_name');
                $table->string('data_type');
                $table->string('referenced_table')->nullable();
                $table->string('referenced_column')->nullable();
                $table->string('referenced_type')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Dokumentiere wichtige FK Beziehungen
        $fkDocs = [
            [
                'table_name' => 'orders',
                'column_name' => 'server_id',
                'data_type' => 'INT UNSIGNED',
                'referenced_table' => 'servers',
                'referenced_column' => 'id',
                'referenced_type' => 'INT UNSIGNED',
                'notes' => 'Typ-kompatibel: Beide INT UNSIGNED. Migration 2026_03_19_204848_add_server_id_to_orders_table verwendet bewusst unsignedInteger statt foreignId.',
            ],
            [
                'table_name' => 'coupon_redemptions',
                'column_name' => 'order_id',
                'data_type' => 'BIGINT UNSIGNED',
                'referenced_table' => 'orders',
                'referenced_column' => 'id',
                'referenced_type' => 'BIGINT UNSIGNED',
                'notes' => 'Typ-kompatibel: Beide BIGINT UNSIGNED durch foreignId.',
            ],
            [
                'table_name' => 'coupon_redemptions',
                'column_name' => 'user_id',
                'data_type' => 'INT UNSIGNED',
                'referenced_table' => 'users',
                'referenced_column' => 'id',
                'referenced_type' => 'INT UNSIGNED',
                'notes' => 'Typ-kompatibel: Beide INT UNSIGNED durch unsignedInteger.',
            ],
            [
                'table_name' => 'coupons',
                'column_name' => 'applies_to_term_id',
                'data_type' => 'BIGINT UNSIGNED',
                'referenced_table' => 'billing_terms',
                'referenced_column' => 'id',
                'referenced_type' => 'BIGINT UNSIGNED',
                'notes' => 'Typ-kompatibel durch foreignId.',
            ],
            [
                'table_name' => 'servers',
                'column_name' => 'user_id (implied through owner)',
                'data_type' => 'MEDIUMINT',
                'referenced_table' => 'users',
                'referenced_column' => 'id',
                'referenced_type' => 'INT UNSIGNED',
                'notes' => 'LEGACY: servers.owner ist MEDIUMINT, nicht direkt als FK definiert. Beachte Typ-Unterschied.',
            ],
        ];

        foreach ($fkDocs as $doc) {
            DB::table('fk_type_documentation')->insertOrIgnore($doc);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fk_type_documentation');
    }
};
