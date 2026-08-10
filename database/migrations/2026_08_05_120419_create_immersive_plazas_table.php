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
        Schema::create('immersive_plazas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_experience_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order')->default(1);
            $table->unsignedInteger('capacity')->default(0);
            $table->string('category_rule')->nullable();
            $table->enum('status', ['borrador', 'activa', 'pausada', 'archivada'])->default('borrador');
            $table->timestamp('published_at')->nullable();
            // Configuración espacial — movida aquí desde `immersive_experiences`
            // porque cada plaza es una instancia física propia (IMM-012).
            $table->json('spawn_point')->nullable();
            $table->json('navigable_bounds')->nullable();
            $table->json('orientation_center')->nullable();
            // Polígonos de zonas prohibidas (monumentos, rutas, accesos) como
            // array de {points: [{x,z}, ...]} — ver §5 "Reglas obligatorias".
            $table->json('excluded_zones')->nullable();
            $table->enum('mobile_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('ligero');
            $table->enum('desktop_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('alto');
            // IMM-013 redefinido: imagen de referencia que el administrador
            // interpreta a simple vista para ubicar reservas de stand. Se
            // asume que cubre exactamente el rectángulo de `navigable_bounds`
            // — así un clic en la imagen se traduce a coordenadas de mundo
            // por calibración lineal (ver ImmersiveExperience/StandSlot).
            $table->string('reference_image_path')->nullable();
            $table->unsignedInteger('reference_image_width')->nullable();
            $table->unsignedInteger('reference_image_height')->nullable();
            $table->timestamps();

            $table->unique(['immersive_experience_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_plazas');
    }
};
