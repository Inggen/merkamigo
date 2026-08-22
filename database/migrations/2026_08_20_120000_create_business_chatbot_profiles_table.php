<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: negocios con el chatbot IA (plan Emprendedor o el
 * add-on) pueden darle al asistente contexto propio — un PDF con
 * información del negocio, notas sueltas, y el tono/jerga con la que
 * habla ("mijito", "sumercé", "chinito"...) — para que responda con más
 * datos reales y suene a la propia voz del negocio. `AnswerBusinessChatQuestion`
 * lee esta fila (si existe) al armar el contexto de cada respuesta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_chatbot_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('tone')->nullable();
            $table->text('extra_notes')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_original_name')->nullable();
            $table->text('document_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_chatbot_profiles');
    }
};
