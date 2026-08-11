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
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            // Repetición de textura (`{u, v}`) elegida libremente por
            // instancia — nunca en `immersive_object_templates` (la
            // plantilla es compartida por N instancias; ponerlo ahí
            // forzaría el mismo tiling para todas). Nulo = sin override,
            // se usa 1/1 (tiling por defecto del material).
            $table->json('texture_tiling')->nullable()->after('scale_vector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('texture_tiling');
        });
    }
};
