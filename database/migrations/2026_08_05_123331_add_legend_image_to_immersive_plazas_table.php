<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            // Imagen separada de la leyenda (colores → tipo de objeto),
            // distinta de `reference_image_path` (el plano en sí) — el
            // admin sube las dos, el sistema detecta los colores de cada
            // una por separado antes de poder "Generar ubicaciones".
            $table->string('legend_image_path')->nullable()->after('reference_image_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->dropColumn('legend_image_path');
        });
    }
};
