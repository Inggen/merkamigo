<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfiles sociales reales de Merkamigo (optimización GEO, 2026-10-03):
 * sin esto, `SchemaBuilder::organization()` nunca puede llenar `sameAs`
 * (señal de entidad para Google/LLMs) y el pie de página no tiene a dónde
 * enlazar sus íconos de Facebook/Instagram salvo las páginas genéricas de
 * esas redes. Todos nullable: se llenan desde Filament, nunca se infieren.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('social_facebook_url')->nullable()->after('meta_pixel_id');
            $table->string('social_instagram_url')->nullable()->after('social_facebook_url');
            $table->string('social_tiktok_url')->nullable()->after('social_instagram_url');
            $table->string('social_whatsapp_url')->nullable()->after('social_tiktok_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['social_facebook_url', 'social_instagram_url', 'social_tiktok_url', 'social_whatsapp_url']);
        });
    }
};
