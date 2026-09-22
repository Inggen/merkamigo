<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estados Merkamigo (Fase 3 de TODO_social.md, Sprint 3). Duración
     * por defecto 24h (`expires_at`, calculado al crear — no hay job de
     * limpieza automática todavía, expirar solo cambia qué se muestra,
     * ver `Story::isActive()`). `product_id` referencia un producto ya
     * existente, nunca lo duplica — mismo criterio que `post_product`.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['imagen', 'promocion', 'producto', 'servicio'])->default('imagen');
            $table->string('image_path');
            $table->string('caption', 280)->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
