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
        Schema::create('immersive_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['borrador', 'validando', 'publicada', 'pausada', 'archivada'])
                ->default('borrador');
            $table->string('thumbnail_path')->nullable();
            // Punto de aparición del personaje {x, y, z, rotationY} y límites
            // navegables {minX, maxX, minZ, maxZ} — ver 4.2 y 9 del TODO
            // inmersivo. El editor espacial real (IMM-013) todavía no existe;
            // por ahora se editan como JSON desde el formulario del admin.
            $table->json('spawn_point')->nullable();
            $table->json('navigable_bounds')->nullable();
            $table->json('orientation_center')->nullable();
            $table->enum('mobile_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('ligero');
            $table->enum('desktop_quality_profile', ['ligero', 'equilibrado', 'alto'])->default('alto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_experiences');
    }
};
