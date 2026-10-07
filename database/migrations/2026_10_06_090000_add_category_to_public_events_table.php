<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categoría del evento público (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 6: "filtros por municipio, fecha y categoría"). Texto corto, no un
 * enum de BD — el panel sugiere una lista curada (música, taller,
 * mercado...) pero no bloquea otras. Aditivo, nullable, no toca filas
 * existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_events', function (Blueprint $table) {
            $table->string('category', 40)->nullable()->after('title');
            $table->index(['status', 'category', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('public_events', function (Blueprint $table) {
            $table->dropIndex(['status', 'category', 'starts_at']);
            $table->dropColumn('category');
        });
    }
};
