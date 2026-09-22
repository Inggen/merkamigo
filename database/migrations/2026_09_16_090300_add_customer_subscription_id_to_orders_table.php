<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada cobro de un periodo de suscripción crea un `Order` más (mismo
 * pipeline de comisión de Merkamigo vía `AccrueCommission`, sin
 * duplicarlo) — este FK marca cuáles vienen de una suscripción en vez de
 * una compra única.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_subscription_id')->nullable()->after('product_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_subscription_id');
        });
    }
};
