<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Credenciales de Wompi de CADA negocio (decisión del usuario, sesión
     * del 15 sep 2026): el pago de un cliente por un producto va directo
     * a la cuenta Wompi del propio negocio, nunca a la de Merkamigo —
     * Merkamigo no recauda dinero de terceros. Solo se cobra su comisión
     * aparte, contra la tarjeta ya guardada para la suscripción (ver
     * `commission_charges`).
     *
     * Llaves sensibles cifradas a nivel de aplicación (cast `encrypted`
     * en el modelo) — a diferencia de `wompi_settings` (que es la propia
     * cuenta de Merkamigo, con menor superficie de riesgo), aquí se
     * custodian credenciales de terceros, así que el estándar de
     * protección es más alto.
     */
    public function up(): void
    {
        Schema::create('business_wompi_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_key');
            $table->text('private_key');
            $table->text('integrity_secret');
            $table->text('events_secret');
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_wompi_credentials');
    }
};
