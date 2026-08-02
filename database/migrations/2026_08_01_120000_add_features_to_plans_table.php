<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('features')->nullable()->after('limits');
        });

        DB::table('plans')->where('slug', 'gratis')->update([
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 10 productos o servicios',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
            ]),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
