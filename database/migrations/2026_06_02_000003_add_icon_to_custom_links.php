<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('custom_links', function (Blueprint $table) {
            $table->string('icon', 64)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('custom_links', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
