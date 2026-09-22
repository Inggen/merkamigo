<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Seguir negocios" (2.3 del TODO, alcance del Sprint 2 — perfiles,
     * categorías y municipios quedan para más adelante, este Sprint solo
     * pide "Seguir negocios" en la lista de tareas de la Fase 28).
     * Tabla nueva genuina: a diferencia de `favorites` (guardar), seguir
     * implica notificación de contenido nuevo, no solo un marcador.
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
