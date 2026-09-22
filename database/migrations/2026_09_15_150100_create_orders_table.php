<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido de un producto (checkout, Sprint aparte de TODO_social.md
     * Fase 7 — re-planeado tras confirmar con el usuario que Wompi no
     * soporta split payments: el pago va directo a la cuenta Wompi DEL
     * NEGOCIO, `orders` aquí es solo el registro de lo que pasó, nunca
     * mueve el dinero). Un pedido = un producto (sin carrito todavía,
     * mismo criterio de alcance mínimo que el resto de este TODO).
     * `commission_cents` es lo que le corresponde a Merkamigo por esta
     * venta, cobrado después y aparte — ver `commission_charges`
     * (`commission_charge_id` se agrega en una migración posterior, una
     * vez existe esa tabla).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('COP');
            $table->unsignedBigInteger('commission_cents')->default(0);
            $table->string('reference')->unique();
            $table->string('wompi_transaction_id')->nullable();
            $table->enum('status', ['pendiente', 'pagado', 'rechazado', 'cancelado'])->default('pendiente');
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['buyer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
