<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: catálogo administrable de formas de pago (Nequi,
 * Visa, Pago contraentrega, etc.), cada una con su logo, para que el
 * emprendedor escoja cuáles acepta y se muestren en su vitrina — mismo
 * criterio estructural que `business_attributes`, pero con imagen y una
 * relación real (`business_payment_method`) en vez de un array de slugs,
 * porque aquí sí necesitamos poder listar/editar el logo de cada una
 * desde el admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('business_payment_method', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->primary(['business_id', 'payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_payment_method');
        Schema::dropIfExists('payment_methods');
    }
};
