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
        Schema::table('immersive_experiences', function (Blueprint $table) {
            // Se mueven a `immersive_plazas`: una experiencia puede tener
            // varias plazas (IMM-012) y cada una es una instancia física
            // distinta, así que el punto de aparición, los límites
            // navegables y la calidad son por plaza, no por experiencia.
            $table->dropColumn([
                'spawn_point',
                'navigable_bounds',
                'orientation_center',
                'mobile_quality_profile',
                'desktop_quality_profile',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_experiences', function (Blueprint $table) {
            $table->json('spawn_point')->nullable();
            $table->json('navigable_bounds')->nullable();
            $table->json('orientation_center')->nullable();
            $table->enum('mobile_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('ligero');
            $table->enum('desktop_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('alto');
        });
    }
};
