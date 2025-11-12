<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 32)->unique();
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->enum('type', ['amount', 'percentage', 'resource', 'duration'])->default('percentage');
                $table->decimal('value', 12, 4)->nullable();
                $table->decimal('percentage', 5, 2)->nullable();
                $table->unsignedInteger('max_usages')->nullable();
                $table->unsignedInteger('per_user_limit')->nullable();
                $table->foreignId('applies_to_term_id')->nullable()->constrained('billing_terms')->nullOnDelete();
                $table->unsignedInteger('usage_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['code', 'is_active']);
                $table->index(['type', 'is_active']);
                $table->index(['expires_at']);
            });
        }

        if (!Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 12, 4);
                $table->json('metadata')->nullable();
                $table->timestamp('redeemed_at');
                $table->timestamps();

                $table->index(['coupon_id', 'user_id']);
                $table->index(['user_id', 'redeemed_at']);
                $table->index(['order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};