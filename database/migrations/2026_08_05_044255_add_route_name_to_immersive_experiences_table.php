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
        Schema::table('immersive_experiences', function (Blueprint $table) {
            // Nombre de la ruta Laravel que sirve la escena de esta
            // experiencia (p. ej. "labs.zipa-inmersiva"). Las escenas siguen
            // siendo código/assets fijos por municipio (decisión de
            // arquitectura #1 del TODO) — esto solo hace que CUÁL escena
            // está activa para cada experiencia sea configurable desde el
            // admin en vez de un `match()` hardcodeado por slug de municipio.
            $table->string('route_name')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_experiences', function (Blueprint $table) {
            $table->dropColumn('route_name');
        });
    }
};
