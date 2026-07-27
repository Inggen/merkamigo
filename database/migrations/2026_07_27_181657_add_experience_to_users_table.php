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
            // "Recordar la última experiencia utilizada" (0.2.1 del TODO):
            // qué front vio por última vez (Cliente o Emprendedor). Nula
            // hasta que el usuario elija una por primera vez.
            $table->enum('experience', ['cliente', 'emprendedor'])->nullable()->after('phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('experience');
        });
    }
};
