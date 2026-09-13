<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Video de fondo de la tarjeta "Crea tu vitrina gratis" en Inicio,
     * configurable desde el admin. Sin video, la tarjeta conserva su
     * imagen estática actual.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('create_vitrina_video_path')->nullable()->after('meta_pixel_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('create_vitrina_video_path');
        });
    }
};
