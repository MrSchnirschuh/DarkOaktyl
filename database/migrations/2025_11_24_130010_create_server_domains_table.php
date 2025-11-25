<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('server_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreignId('domain_root_id')->nullable()->constrained('domain_roots')->nullOnDelete();
            $table->string('type')->default('managed');
            $table->string('hostname')->unique();
            $table->string('subdomain')->nullable();
            $table->string('status')->default('pending');
            $table->string('verification_method')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_domains');
    }
};
