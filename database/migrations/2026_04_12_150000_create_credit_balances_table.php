<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->decimal('balance', 15, 4)->default(0.0000);
            $table->decimal('reserved_balance', 15, 4)->default(0.0000);
            $table->decimal('low_balance_threshold', 10, 4)->default(5.0000);
            $table->boolean('low_balance_warning_sent')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_balances');
    }
};
