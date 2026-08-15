<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imágenes de marca/branding adicionales para el singleton de
     * configuración del sitio: fondo del login de administración, fondo
     * del pie de página, apple-touch-icon, fondo del buscador principal,
     * logo y logo monocromático.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('login_background_path')->nullable()->after('default_share_image_path');
            $table->string('footer_background_path')->nullable()->after('login_background_path');
            $table->string('apple_touch_icon_path')->nullable()->after('footer_background_path');
            $table->string('main_search_background_path')->nullable()->after('apple_touch_icon_path');
            $table->string('logo_path')->nullable()->after('main_search_background_path');
            $table->string('logo_mono_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'login_background_path',
                'footer_background_path',
                'apple_touch_icon_path',
                'main_search_background_path',
                'logo_path',
                'logo_mono_path',
            ]);
        });
    }
};
