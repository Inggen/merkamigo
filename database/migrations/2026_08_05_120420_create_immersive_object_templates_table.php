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
        Schema::create('immersive_object_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Categoría del objeto (redefinición de IMM-013): un solo
            // catálogo cubre todo lo que puede colocarse en una plaza.
            // 'stand' se reserva vía `stand_slots` (flujo comercial); el
            // resto se coloca directo como `immersive_plaza_props`.
            $table->enum('category', ['stand', 'construccion', 'arbol', 'fuente', 'monumento', 'personaje'])
                ->default('stand');
            $table->decimal('max_width', 8, 2);
            $table->decimal('max_depth', 8, 2);
            $table->decimal('max_height', 8, 2);
            // Eje frontal declarado por la plantilla, en radianes sobre Y,
            // para que el motor sepa hacia dónde mira el modelo "de fábrica"
            // y no quede al revés al aplicar la rotación del slot (§5).
            $table->float('front_axis_rotation')->default(0);
            $table->string('thumbnail_path')->nullable();
            $table->json('allowed_colors')->nullable();
            // IMM-020 (Fase 2) llena esto; hasta entonces la plantilla es
            // reservable pero no renderizable en la escena real.
            $table->string('model_path')->nullable();
            $table->json('lod_config')->nullable();
            $table->enum('status', ['borrador', 'publicada', 'archivada'])->default('borrador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_object_templates');
    }
};
