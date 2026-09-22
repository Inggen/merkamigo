<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivote "Producto/Servicio -> Crear publicación" (2.2 del TODO):
     * solo referencia productos ya existentes del negocio, nunca los
     * duplica.
     */
    public function up(): void
    {
        Schema::create('post_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product');
    }
};
