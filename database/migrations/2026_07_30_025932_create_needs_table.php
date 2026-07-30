<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Nullable: un borrador puede empezar sin municipio todavía
            // (igual que `businesses.municipality_id`) — `PublishNeed` exige
            // que esté presente antes de publicar.
            $table->foreignId('municipality_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zone')->nullable();
            $table->string('title');
            $table->text('description');
            $table->decimal('budget', 10, 2)->nullable();
            $table->string('status')->default('borrador');
            $table->string('outcome')->nullable();
            // Sin constraint de FK a `offers` a propósito: `offers.need_id` ya
            // referencia a `needs`, y esta columna solo guarda cuál de esas
            // propuestas fue la elegida — una referencia cruzada con FK en
            // ambos sentidos obligaría a crear las tablas en dos pasos sin
            // aportar nada que la integridad a nivel de aplicación no cubra.
            $table->unsignedBigInteger('selected_offer_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['municipality_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs');
    }
};
