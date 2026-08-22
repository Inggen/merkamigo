<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: que el emprendedor pueda ver quién le escribió a su
 * chatbot y qué se dijo. Los visitantes del chat no inician sesión, así
 * que no hay una identidad real que guardar — `visitor_hash` (mismo
 * criterio que `AnalyticsEvent::visitor_hash`: sha256(ip+user-agent), no
 * la IP en claro) agrupa los mensajes de una misma persona durante una
 * sesión de conversación; `visitor_user_id`/`visitor_label` solo se
 * llenan cuando quien escribe sí tiene sesión iniciada como cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->foreignId('visitor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_label')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('last_message_at');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'visitor_hash', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_chat_conversations');
    }
};
