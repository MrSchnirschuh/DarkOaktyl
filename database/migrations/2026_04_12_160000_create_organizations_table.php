<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('uuid', 36)->unique();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->boolean('is_active')->default(true);
            $table->decimal('monthly_budget', 10, 2)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('uuid');
            $table->index('owner_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
