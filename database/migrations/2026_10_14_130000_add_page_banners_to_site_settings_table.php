<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido del usuario (2026-10-14): poder editar desde el admin los
 * banners (imagen + título + descripción) de Vitrinas, Eventos,
 * Merkapuntos y las otras páginas que resultaron tener el mismo patrón
 * (Merkamigo Premia, Pídelo, la bienvenida de "Crear vitrina") — hasta
 * ahora todos hardcodeados en cada vista. Vitrinas ya tenía la imagen
 * editable (`main_search_background_path`), solo le faltaban título y
 * descripción. Todo queda `nullable`: sin valor cargado desde Filament,
 * cada vista sigue mostrando su copy/imagen original tal cual (ver
 * `SiteSetting::eventosBannerUrl()` etc. y el `??` en cada vista).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('vitrinas_hero_title')->nullable()->after('main_search_background_path');
            $table->text('vitrinas_hero_description')->nullable()->after('vitrinas_hero_title');

            $table->string('eventos_banner_path')->nullable()->after('vitrinas_hero_description');
            $table->string('eventos_banner_title')->nullable()->after('eventos_banner_path');
            $table->text('eventos_banner_description')->nullable()->after('eventos_banner_title');

            $table->string('merkapuntos_banner_path')->nullable()->after('eventos_banner_description');
            $table->string('merkapuntos_banner_title')->nullable()->after('merkapuntos_banner_path');
            $table->text('merkapuntos_banner_description')->nullable()->after('merkapuntos_banner_title');

            $table->string('premia_banner_path')->nullable()->after('merkapuntos_banner_description');
            $table->string('premia_banner_title')->nullable()->after('premia_banner_path');
            $table->text('premia_banner_description')->nullable()->after('premia_banner_title');

            $table->string('pidelo_banner_path')->nullable()->after('premia_banner_description');
            $table->string('pidelo_banner_title')->nullable()->after('pidelo_banner_path');
            $table->text('pidelo_banner_description')->nullable()->after('pidelo_banner_title');

            $table->string('bienvenida_banner_path')->nullable()->after('pidelo_banner_description');
            $table->string('bienvenida_banner_title')->nullable()->after('bienvenida_banner_path');
            $table->text('bienvenida_banner_description')->nullable()->after('bienvenida_banner_title');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'vitrinas_hero_title',
                'vitrinas_hero_description',
                'eventos_banner_path',
                'eventos_banner_title',
                'eventos_banner_description',
                'merkapuntos_banner_path',
                'merkapuntos_banner_title',
                'merkapuntos_banner_description',
                'premia_banner_path',
                'premia_banner_title',
                'premia_banner_description',
                'pidelo_banner_path',
                'pidelo_banner_title',
                'pidelo_banner_description',
                'bienvenida_banner_path',
                'bienvenida_banner_title',
                'bienvenida_banner_description',
            ]);
        });
    }
};
