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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('wompi_transaction_id')->nullable()->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency')->default('COP');
            $table->string('status')->default('pendiente');
            $table->string('coupon_code')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
