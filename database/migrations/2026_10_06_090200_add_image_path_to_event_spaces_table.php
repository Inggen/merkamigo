<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto opcional de un espacio/sala de eventos (panel del negocio, Fase
 * 2 · pestaña Agenda) — aditivo, nullable, no toca filas existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_spaces', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('event_spaces', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
