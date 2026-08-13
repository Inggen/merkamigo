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
        Schema::create('immersive_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_plaza_id')->constrained()->cascadeOnDelete();
            // Nullable a propósito: eventos como "entrada a la plaza" o
            // "búsqueda" no están ligados a ningún negocio. Distinto de
            // `analytics_events.business_id` (obligatorio ahí porque TODOS
            // sus eventos son de un negocio) — aquí el sujeto principal es
            // la plaza, el negocio es un dato secundario opcional. `nullOnDelete`
            // (no cascade) para no perder el historial de la plaza si el
            // negocio referenciado se elimina después.
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->nullableMorphs('subject');
            $table->string('visitor_hash', 64)->nullable();
            // Datos pequeños y no personales: texto de búsqueda, slug de
            // categoría, tier de dispositivo — nunca IP/user-agent en crudo
            // (igual que `analytics_events`, ver `RegisterImmersiveEvent`).
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['immersive_plaza_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_events');
    }
};
