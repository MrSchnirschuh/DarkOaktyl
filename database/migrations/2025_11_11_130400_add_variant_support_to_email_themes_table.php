<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_themes', function (Blueprint $table) {
            $table->string('variant_mode')->default('single');
            $table->json('light_palette')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('email_themes', function (Blueprint $table) {
            $table->dropColumn(['variant_mode', 'light_palette']);
        });
    }
};
