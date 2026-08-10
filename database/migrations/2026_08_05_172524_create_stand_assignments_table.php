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
        Schema::create('stand_assignments', function (Blueprint $table) {
            $table->id();
            // Una asignación por negocio (IMM-021: "Mi stand en la plaza",
            // singular). `cascadeOnDelete` porque una asignación no tiene
            // sentido sin el negocio dueño.
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('immersive_plaza_id')->nullable()->constrained()->nullOnDelete();
            // Único (no solo por negocio): "un slot solo puede tener una
            // asignación activa" (§5 del TODO). Varias filas con NULL están
            // permitidas (sin_cupo/pausado no ocupan ningún slot).
            $table->foreignId('stand_slot_id')->nullable()->unique()->constrained()->nullOnDelete();
            // Slot anterior, para "al reactivar, intenta recuperar el slot
            // anterior; si está ocupado, asigna el siguiente compatible".
            $table->foreignId('previous_slot_id')->nullable()->constrained('stand_slots')->nullOnDelete();
            $table->foreignId('object_template_id')->nullable()->constrained('immersive_object_templates')->nullOnDelete();
            $table->enum('status', [
                'sin_configurar', 'pendiente', 'publicado', 'pausado', 'sin_cupo', 'reubicacion_requerida',
            ])->default('sin_configurar');
            $table->string('motivo_reubicacion')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stand_assignments');
    }
};
