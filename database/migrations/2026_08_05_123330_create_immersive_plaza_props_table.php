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
        Schema::create('immersive_plaza_props', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_plaza_id')->constrained()->cascadeOnDelete();
            $table->foreignId('object_template_id')->constrained('immersive_object_templates')->cascadeOnDelete();
            // Igual que en stand_slots: posición en la imagen de referencia
            // (para redibujar sobre la miniatura) y en coordenadas de mundo
            // (para el motor 3D) — ver redefinición de IMM-013.
            $table->json('image_position')->nullable();
            $table->json('world_position');
            $table->json('rotation')->nullable();
            $table->float('scale')->default(1);
            $table->enum('source', ['manual', 'auto_detected'])->default('manual');
            $table->enum('status', ['borrador', 'confirmado'])->default('borrador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_plaza_props');
    }
};
