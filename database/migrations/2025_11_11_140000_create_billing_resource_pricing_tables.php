<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('billing_resource_prices')) {
            Schema::create('billing_resource_prices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('resource', 64);
                $table->string('display_name', 191);
                $table->text('description')->nullable();
                $table->string('unit', 32)->nullable();
                $table->unsignedInteger('base_quantity')->default(1);
                $table->decimal('price', 12, 4);
                $table->char('currency', 3)->default(strtoupper(config('modules.billing.currency.code', 'USD')));
                $table->unsignedInteger('min_quantity')->default(0);
                $table->unsignedInteger('max_quantity')->nullable();
                $table->unsignedInteger('default_quantity')->default(0);
                $table->unsignedInteger('step_quantity')->default(1);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_metered')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['resource', 'currency']);
            });
        }

        if (!Schema::hasTable('billing_resource_scaling_rules')) {
            Schema::create('billing_resource_scaling_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('resource_price_id')->constrained('billing_resource_prices')->cascadeOnDelete();
                $table->unsignedInteger('threshold')->default(0);
                $table->decimal('multiplier', 12, 4)->default(1);
                $table->string('mode', 32)->default('multiplier');
                $table->string('label', 191)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['resource_price_id', 'threshold', 'mode']);
            });
        }

        if (!Schema::hasTable('billing_terms')) {
            Schema::create('billing_terms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 191);
                $table->string('slug', 191)->unique();
                $table->unsignedInteger('duration_days');
                $table->decimal('multiplier', 12, 4)->default(1);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_terms');
        Schema::dropIfExists('billing_resource_scaling_rules');
        Schema::dropIfExists('billing_resource_prices');
    }
};
