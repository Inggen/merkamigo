<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ID del Meta Pixel (Facebook Pixel), configurable desde el admin para
     * insertar el script de seguimiento sin tocar código.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('meta_pixel_id')->nullable()->after('logo_mono_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('meta_pixel_id');
        });
    }
};
