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
        Schema::table('users', function (Blueprint $table) {
            // Preset 'hombre'/'mujer' del personaje 3D (IMM-030), elegido en
            // /settings/avatar. Nulo = "no elegido" (se resuelve a 'hombre'
            // donde se consume, igual que el default de avatar-preference.js).
            // Distinto de `avatar_path` (foto de perfil real, sin relación).
            $table->string('avatar_preset', 10)->nullable()->after('avatar_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_preset');
        });
    }
};
