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
        Schema::create('experience_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_experience_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            // Copia completa de los campos versionables de la experiencia en
            // el momento de publicar (IMM-014): permite comparar/revertir sin
            // que una edición en curso afecte lo que ya ven los visitantes.
            $table->json('config_snapshot');
            $table->string('checksum');
            $table->enum('status', ['borrador', 'publicada', 'archivada'])->default('borrador');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['immersive_experience_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experience_versions');
    }
};
