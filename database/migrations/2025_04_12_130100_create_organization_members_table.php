<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('role')->default('member'); // owner, admin, member
            $table->timestamp('joined_at')->nullable();
            $table->decimal('monthly_share_amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // manual, stripe, paypal
            $table->string('billing_email')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};