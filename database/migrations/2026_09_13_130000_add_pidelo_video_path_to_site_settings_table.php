<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Video de fondo de la tarjeta "¿No encuentras lo que necesitas?" en
     * Inicio, configurable desde el admin. Sin video, la tarjeta conserva
     * su fondo plano actual.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('pidelo_video_path')->nullable()->after('create_vitrina_video_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('pidelo_video_path');
        });
    }
};
