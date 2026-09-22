<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra qué usuario ya vio un estado (Fase 3 del TODO social:
     * "registrar visualizaciones"), también usado para el anillo
     * visto/no visto del carrusel de estados. Solo usuarios autenticados
     * — igual que `recently_viewed_businesses`, no se rastrean invitados.
     */
    public function up(): void
    {
        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['story_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_views');
    }
};
