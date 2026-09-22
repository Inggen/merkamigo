<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acceso de un cliente a un producto digital (Fase 9 del TODO social) —
 * no confundir con `business_entitlements` (capacidades desbloqueadas
 * para un NEGOCIO comprándole a Merkamigo, dominio distinto). `expires_at`
 * nulo = compra única, permanente; con fecha = ligado a una suscripción
 * vigente, se refresca en cada cobro de periodo aprobado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
