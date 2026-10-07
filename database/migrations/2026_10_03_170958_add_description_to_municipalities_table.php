<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimización GEO (2026-10-03): la Plaza de cada municipio solo mostraba
 * una grilla de negocios, sin un párrafo propio que un buscador o un LLM
 * pudiera citar al responder sobre comercio local en ese municipio. Campo
 * editorial editable desde Filament, con contenido inicial sembrado en
 * `MunicipalitySeeder` — igual criterio que el resto de contenido
 * editable (planes, FAQ): nunca codificado directamente en la vista.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->text('description')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
