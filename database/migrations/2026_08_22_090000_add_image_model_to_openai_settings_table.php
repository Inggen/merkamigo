<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario: campo aparte para el modelo de generación de
 * IMÁGENES (a diferencia de `model`, que es para texto) — todavía no hay
 * ninguna función en el código que genere imágenes, este campo deja la
 * configuración lista para cuando se construya esa función. A diferencia
 * de `model`, aquí sí se deja un valor inicial real (pedido explícito del
 * usuario) en vez de dejarlo vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('openai_settings', function (Blueprint $table) {
            $table->string('image_model')->nullable()->default('gpt-image-2')->after('model');
        });

        DB::table('openai_settings')->whereNull('image_model')->update(['image_model' => 'gpt-image-2']);
    }

    public function down(): void
    {
        Schema::table('openai_settings', function (Blueprint $table) {
            $table->dropColumn('image_model');
        });
    }
};
