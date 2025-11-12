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
                $table->string('code', 64)->unique();
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->string('type', 32)->default('amount');
                $table->decimal('value', 12, 4)->nullable();
                $table->decimal('percentage', 7, 4)->nullable();
                $table->unsignedInteger('max_usages')->nullable();
                $table->unsignedInteger('per_user_limit')->nullable();
                $table->foreignId('applies_to_term_id')->nullable()->constrained('billing_terms')->nullOnDelete();
                $table->unsignedInteger('created_by_id')->nullable();
                $table->unsignedInteger('updated_by_id')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->unsignedInteger('user_id')->nullable();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->decimal('amount', 12, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamp('redeemed_at');
                $table->timestamps();

                $table->index(['coupon_id', 'user_id']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
