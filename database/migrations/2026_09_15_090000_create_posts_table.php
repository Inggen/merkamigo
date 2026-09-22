<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publicaciones de negocios (Fase 2.2 de TODO_social.md, Sprint 2).
     * Un post siempre pertenece a un negocio (Merkamigo es comercio local
     * primero, ver sección 0 del TODO) y a la persona que lo publicó
     * dentro de ese negocio — nunca se duplica info de producto/servicio,
     * `post_product` solo referencia productos ya existentes.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['texto', 'imagen', 'carrusel', 'promocion'])->default('texto');
            $table->text('body')->nullable();
            $table->enum('status', ['borrador', 'publicado', 'oculto'])->default('borrador');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status', 'published_at']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
