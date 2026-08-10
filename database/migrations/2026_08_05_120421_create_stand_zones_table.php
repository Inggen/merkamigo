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
        Schema::create('stand_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_plaza_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Polígono permitido: {points: [{x, z}, ...]} en coordenadas de
            // mundo (mismo sistema que `navigable_bounds` de la plaza).
            $table->json('polygon');
            $table->enum('default_orientation', ['TOWARD_CENTER', 'AWAY_FROM_CENTER', 'FOLLOW_PATH', 'MANUAL'])
                ->default('TOWARD_CENTER');
            $table->json('reference_center')->nullable();
            $table->float('min_separation')->default(1.5);
            $table->unsignedInteger('priority')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stand_zones');
    }
};
