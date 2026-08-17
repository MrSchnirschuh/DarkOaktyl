<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('domain_roots', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('root_domain');
            $table->string('provider')->default('manual');
            $table->json('provider_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('provider');
            $table->unique('root_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_roots');
    }
};
