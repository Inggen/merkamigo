<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fila única (singleton, mismo criterio que `openai_settings`): el PDF
 * de contexto general que el admin sube para que el asistente de la
 * plataforma (personaje flotante fuera de una vitrina) lo use como
 * conocimiento real, además de categorías/municipios/preguntas
 * frecuentes — pedido del usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_path')->nullable();
            $table->string('document_original_name')->nullable();
            $table->text('document_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_knowledge_documents');
    }
};
