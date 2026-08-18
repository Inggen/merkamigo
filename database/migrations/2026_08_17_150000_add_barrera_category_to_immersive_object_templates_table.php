<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: "tipo de objeto especial" estrictamente
     * colisionante — nueva categoría para clasificarlo en el catálogo,
     * junto a stand/construcción/árbol/fuente/monumento/personaje.
     * `->change()` reescribe el ENUM nativo tanto en MySQL (`MODIFY
     * COLUMN`) como en SQLite (recrea la tabla) sin depender de
     * `doctrine/dbal`, que este proyecto no tiene instalado.
     */
    public function up(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->enum('category', ['stand', 'construccion', 'arbol', 'fuente', 'monumento', 'personaje', 'barrera'])
                ->default('stand')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->enum('category', ['stand', 'construccion', 'arbol', 'fuente', 'monumento', 'personaje'])
                ->default('stand')
                ->change();
        });
    }
};
