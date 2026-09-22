<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8/9 del TODO social (Sprint 6): "permitir marcar producto/servicio
 * como compra única o suscripción" y vender productos digitales. `type`
 * gana `digital` (ebooks, cursos, membresías, archivos); `sale_type` es
 * independiente porque tanto un producto físico como uno digital podrían,
 * a futuro, venderse por suscripción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('sale_type', ['unica', 'suscripcion'])->default('unica')->after('type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['producto', 'servicio', 'digital'])->default('producto')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['producto', 'servicio'])->default('producto')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sale_type');
        });
    }
};
