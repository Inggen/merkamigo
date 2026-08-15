<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: controlar la niebla (fog) de la escena 3D por
     * plaza desde el editor espacial, en vez del valor fijo que trae
     * `VoxelPlazaEngine` por defecto ({color: 0xb6d7f3, near: 78, far: 260}).
     * Nulo (plaza sin configurar) preserva ese mismo comportamiento —
     * `ImmersivePlaza::fogSettings()` cae a esos valores.
     */
    public function up(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->json('fog')->nullable()->after('reference_image_height');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->dropColumn('fog');
        });
    }
};
