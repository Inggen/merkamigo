<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: poder girar el fondo con textura equirectangular
     * hasta 360 grados (ej. para que el horizonte de la imagen quede
     * alineado con la plaza). Grados (0-360), no radianes — mismo criterio
     * que el resto de campos de rotación editables desde el admin.
     */
    public function up(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->float('sky_rotation')->default(0)->after('sky_image_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->dropColumn('sky_rotation');
        });
    }
};
