<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: activar/desactivar el fondo con textura
     * equirectangular (spheremap) sin tener que borrar la imagen ya
     * subida en "Editar Plaza". Por defecto `true` (también para filas
     * existentes, vía el default de la columna) — así una plaza con
     * imagen ya cargada se sigue viendo exactamente igual que antes de
     * que existiera este control.
     */
    public function up(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->boolean('sky_image_enabled')->default(true)->after('sky_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->dropColumn('sky_image_enabled');
        });
    }
};
