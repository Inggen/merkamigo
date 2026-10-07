<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo post↔evento público (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 5: "Acción 'Publicar en el feed' desde un evento publicado: crear
 * post vinculado... impedir posts duplicados"). Único en BD, no solo a
 * nivel de aplicación — así dos clics simultáneos en "Publicar en el
 * feed" nunca producen dos posts del mismo evento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('public_event_id')->nullable()->unique()->after('user_id')
                ->constrained('public_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('public_event_id');
        });
    }
};
