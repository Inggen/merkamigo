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
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            // IMM-020: la mayoría de objetos del catálogo (incluidos los tres
            // stands iniciales) son voxel procedurales — una clave de
            // `standardBuilders` en `voxel-plaza-engine.js`, no un GLB. Se
            // guarda aparte de `model_path` (reservado para objetos con
            // modelo GLB real, como la catedral) para no mezclar los dos
            // mecanismos de renderizado en el mismo campo.
            $table->string('builder_key')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->dropColumn('builder_key');
        });
    }
};
