<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El QR de identificación (F1.8) necesita poder volver a mostrarse en cada
 * visita a "Merkapuntos" sin rotar el token — un QR que cambia cada vez
 * que el cliente abre la página es inutilizable (no se puede guardar ni
 * imprimir). Se guarda también una copia cifrada (reversible con
 * `APP_KEY`, cast `encrypted` de Eloquent) junto al hash ya existente: el
 * hash sigue siendo lo único que se usa para resolver un escaneo, el
 * valor cifrado solo se descifra para mostrárselo de nuevo a su propio
 * dueño.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_identity_tokens', function (Blueprint $table) {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_identity_tokens', function (Blueprint $table) {
            $table->dropColumn('token_encrypted');
        });
    }
};
