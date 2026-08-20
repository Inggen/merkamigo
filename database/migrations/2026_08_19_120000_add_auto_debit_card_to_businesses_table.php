<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: débito automático real para la renovación mensual
 * de planes de pago (hoy el checkout de Wompi es de un solo cobro, sin
 * forma de cobrar el mes siguiente). Guarda únicamente lo que Wompi
 * entrega tras tokenizar la tarjeta desde el navegador (nunca el número
 * completo ni el CVV, que jamás tocan este servidor) — el id de la
 * "fuente de pago" para cobrar, y los últimos 4 dígitos/marca solo para
 * mostrarlos en pantalla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('wompi_payment_source_id')->nullable()->after('payment_info');
            $table->string('card_brand')->nullable()->after('wompi_payment_source_id');
            $table->string('card_last_four', 4)->nullable()->after('card_brand');
            $table->boolean('auto_renew_enabled')->default(false)->after('card_last_four');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['wompi_payment_source_id', 'card_brand', 'card_last_four', 'auto_renew_enabled']);
        });
    }
};
