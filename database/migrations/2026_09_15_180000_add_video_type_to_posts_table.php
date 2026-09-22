<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reels (Fase 4 del TODO social, Sprint 4): se reutiliza `posts` con
 * `type = video` en vez de un dominio paralelo — un post de video es un
 * post más para reacciones, comentarios y seguir, solo cambia cómo se
 * muestra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('type', ['texto', 'imagen', 'carrusel', 'promocion', 'video'])
                ->default('texto')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('type', ['texto', 'imagen', 'carrusel', 'promocion'])
                ->default('texto')
                ->change();
        });
    }
};
