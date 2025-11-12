<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'created_by_id')) {
                $table->unsignedInteger('created_by_id')->nullable()->after('applies_to_term_id');
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('coupons', 'updated_by_id')) {
                $table->unsignedInteger('updated_by_id')->nullable()->after('created_by_id');
                $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('coupons', 'personalized_for_id')) {
                $table->unsignedInteger('personalized_for_id')->nullable()->after('updated_by_id');
                $table->foreign('personalized_for_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('coupons', 'parent_coupon_id')) {
                $table->unsignedBigInteger('parent_coupon_id')->nullable()->after('personalized_for_id');
                $table->foreign('parent_coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            }

            if (!Schema::hasColumn('coupons', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('applies_to_term_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'parent_coupon_id')) {
                $table->dropForeign(['parent_coupon_id']);
                $table->dropColumn('parent_coupon_id');
            }

            if (Schema::hasColumn('coupons', 'personalized_for_id')) {
                $table->dropForeign(['personalized_for_id']);
                $table->dropColumn('personalized_for_id');
            }

            if (Schema::hasColumn('coupons', 'updated_by_id')) {
                $table->dropForeign(['updated_by_id']);
                $table->dropColumn('updated_by_id');
            }

            if (Schema::hasColumn('coupons', 'created_by_id')) {
                $table->dropForeign(['created_by_id']);
                $table->dropColumn('created_by_id');
            }

            if (Schema::hasColumn('coupons', 'usage_count')) {
                $table->dropColumn('usage_count');
            }
        });
    }
};
