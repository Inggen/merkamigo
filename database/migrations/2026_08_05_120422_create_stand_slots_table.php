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
        Schema::create('stand_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stand_zone_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->foreignId('stand_template_id')->nullable()->constrained('immersive_object_templates')->nullOnDelete();
            $table->foreignId('allowed_category_id')->nullable()->constrained('categories')->nullOnDelete();
            // Posición sobre la imagen de referencia de la plaza (para
            // redibujar la reserva sobre la miniatura) y posición ya
            // calculada en coordenadas de mundo (para validar y para el
            // motor 3D) — ver redefinición de IMM-013.
            $table->json('image_position')->nullable();
            $table->json('world_position');
            $table->json('rotation')->nullable();
            $table->decimal('max_width', 8, 2);
            $table->decimal('max_depth', 8, 2);
            $table->enum('orientation_mode', ['TOWARD_CENTER', 'AWAY_FROM_CENTER', 'FOLLOW_PATH', 'MANUAL'])
                ->nullable();
            $table->boolean('accessible')->default(true);
            $table->enum('status', ['disponible', 'ocupada', 'bloqueada', 'invalida'])->default('disponible');
            // 'auto_detected': lo creó el detector de color a partir del
            // plano (queda para revisión); 'manual': lo cargó el admin.
            $table->enum('source', ['manual', 'auto_detected'])->default('manual');
            $table->timestamps();

            $table->unique(['stand_zone_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stand_slots');
    }
};
