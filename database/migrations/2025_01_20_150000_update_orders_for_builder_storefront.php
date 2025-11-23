<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->change();
            $table->string('storefront')->default('products')->after('status');
            $table->json('builder_metadata')->nullable()->after('storefront');
            $table->unsignedBigInteger('billing_term_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('node_id')->nullable()->after('billing_term_id');

            $table->index('billing_term_id');
            $table->index('node_id');
            $table->index('storefront');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable(false)->change();
            $table->dropColumn('builder_metadata');
            $table->dropColumn('storefront');
            $table->dropColumn('billing_term_id');
            $table->dropColumn('node_id');
        });
    }
};
