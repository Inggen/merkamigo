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
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            // Ver comentario en la migración gemela de stand_slots — mismo
            // bug (bloqueo solo en memoria, nunca persistido), misma
            // corrección: columna real por fila.
            $table->boolean('locked')->default(false)->after('texture_tiling');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('locked');
        });
    }
};
