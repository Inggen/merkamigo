<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F2.5, aceptación: "reabrir muestra el mismo canje activo
 * correspondiente" — si el cliente cierra el modal o la pestaña, tiene que
 * poder volver a ver el mismo código para presentarlo en el local. Mismo
 * ajuste que ya se hizo para el token de identidad
 * (`2026_10_04_120000_add_encrypted_value_to_loyalty_identity_tokens`):
 * se guarda también una copia cifrada (reversible con `APP_KEY`) junto al
 * hash — el hash sigue siendo lo único que usa el escáner del negocio
 * para validar, la copia cifrada solo se descifra para el propio cliente
 * dueño del canje.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_redemptions', function (Blueprint $table) {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_redemptions', function (Blueprint $table) {
            $table->dropColumn('token_encrypted');
        });
    }
};
