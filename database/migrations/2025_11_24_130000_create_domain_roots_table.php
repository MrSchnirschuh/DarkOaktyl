<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_roots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('root_domain')->unique();
            $table->string('provider')->default('manual');
            $table->json('provider_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_roots');
    }
};
