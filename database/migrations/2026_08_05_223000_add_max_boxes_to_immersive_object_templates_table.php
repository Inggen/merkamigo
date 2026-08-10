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
            // IMM-020b: un objeto grande/complejo (una catedral) necesita
            // muchas más cajas voxel que un stand simple — un límite global
            // único (antes hardcodeado en OpenAiVoxelObjectGenerator) o
            // recorta objetos grandes o deja que uno pequeño abuse del
            // presupuesto. Configurable por plantilla, con el mismo default
            // que tenía el límite global antes de este cambio.
            $table->unsignedInteger('max_boxes')->default(40)->after('max_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->dropColumn('max_boxes');
        });
    }
};
