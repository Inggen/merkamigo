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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['producto', 'servicio'])->default('producto');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('price_type', ['exacto', 'desde', 'consultar', 'sin_precio'])->default('exacto');
            $table->string('unit')->nullable();
            $table->decimal('promo_price', 10, 2)->nullable();
            $table->string('promo_label')->nullable();
            $table->boolean('is_available')->default(true);
            $table->enum('status', ['borrador', 'publicado', 'agotado', 'archivado'])->default('borrador');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
