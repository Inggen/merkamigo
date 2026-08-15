<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: el tiling de un elemento debe quedar bloqueado
     * (cerrado) por defecto para no perder por accidente el que trae desde
     * que fue creado — solo se edita en el Editor espacial (3D) tras
     * desbloquearlo a propósito.
     */
    public function up(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->boolean('texture_tiling_locked')->default(true)->after('texture_tiling');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('texture_tiling_locked');
        });
    }
};
