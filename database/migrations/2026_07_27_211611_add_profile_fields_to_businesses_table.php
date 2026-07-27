<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Consolidado en columnas (no en business_hours/social_links/
            // payment_information separadas) para no sobre-construir en
            // el MVP — ver docs/architecture/decisiones.md.
            $table->string('zone')->nullable()->after('municipality_id');
            $table->string('address')->nullable()->after('zone');
            $table->string('logo_path')->nullable()->after('whatsapp_number');
            $table->json('hours')->nullable()->after('logo_path');
            $table->json('social_links')->nullable()->after('hours');
            $table->text('payment_info')->nullable()->after('social_links');
            $table->json('attributes')->nullable()->after('payment_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['zone', 'address', 'logo_path', 'hours', 'social_links', 'payment_info', 'attributes']);
        });
    }
};
