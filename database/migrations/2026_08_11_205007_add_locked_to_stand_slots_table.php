<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stand_slots', function (Blueprint $table) {
            // Bloqueo contra movimiento accidental en el editor espacial —
            // antes vivía solo en memoria (`PlazaSpatialEditor::
            // $lockedObjectKeys`, una propiedad Livewire sin persistencia
            // real), por eso siempre volvía a aparecer desbloqueado al
            // recargar la página. Ahora es una columna real por fila.
            $table->boolean('locked')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stand_slots', function (Blueprint $table) {
            $table->dropColumn('locked');
        });
    }
};
