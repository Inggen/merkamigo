<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Terreno para "inventario local" de Google Merchant Center (fichas
     * locales sin costo / anuncios de inventario local — distinto del feed
     * de productos online ya implementado). `has_physical_location` lo
     * marca el propio emprendedor al crear o editar su vitrina; nunca se
     * asume por geolocalización u otra heurística. `google_business_store_code`
     * es el `store_code` de SU PROPIO Perfil de Empresa de Google, no el de
     * Merkamigo — cada local físico real tiene el suyo. Ambos quedan
     * inertes hasta que se cumplan las dos condiciones: el negocio los
     * completa Y `GOOGLE_MERCHANT_LOCAL_INVENTORY_ENABLED=true` (ver
     * TODO-Google-Merchant.md, sección post-cierre).
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('has_physical_location')->default(false)->after('longitude');
            $table->string('google_business_store_code')->nullable()->after('has_physical_location');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['has_physical_location', 'google_business_store_code']);
        });
    }
};
