<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: mejorar la tarjeta de atributos de la vitrina
 * pública (antes solo texto plano) con un ícono y una descripción corta
 * por atributo. Ambos opcionales — un atributo sin ícono/descripción
 * sigue mostrándose como antes (solo el nombre), no rompe nada existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_attributes', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('slug');
            $table->string('description')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('business_attributes', function (Blueprint $table) {
            $table->dropColumn(['icon', 'description']);
        });
    }
};
