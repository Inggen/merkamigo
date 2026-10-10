<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR4 de TODO_VENTAS_RENTABILIDAD.md (pedido del usuario: "deja el
     * cobro mensual igual de robusto que la renovación de planes") —
     * mismo periodo de gracia que `Subscription` (`status` +
     * `grace_ends_at`): si el cobro automático falla o no hay tarjeta
     * guardada, el negocio no pierde el acceso de inmediato, tiene unos
     * días para regularizar. `status` nace en `activa` por defecto —
     * ningún entitlement existente (de por vida o no) estaba en gracia
     * antes de que existiera este concepto.
     */
    public function up(): void
    {
        Schema::table('business_entitlements', function (Blueprint $table) {
            $table->enum('status', ['activa', 'en_gracia'])->default('activa')->after('key');
            $table->timestamp('grace_ends_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('business_entitlements', function (Blueprint $table) {
            $table->dropColumn(['status', 'grace_ends_at']);
        });
    }
};
