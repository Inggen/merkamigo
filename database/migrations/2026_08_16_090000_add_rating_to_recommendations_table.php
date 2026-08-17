<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: el resumen de "Opiniones de clientes" de la
     * vitrina mostraba 5 estrellas fijas que no salían de ningún dato real
     * — se agrega una calificación (1-5) real a cada `Recommendation`.
     * Nullable porque las recomendaciones ya existentes antes de esta
     * migración no la tienen (no se puede rellenar retroactivamente); de
     * aquí en adelante las dos vías de envío (`SubmitOpinion` y
     * `SubmitRecommendation`) la exigen siempre.
     */
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};
