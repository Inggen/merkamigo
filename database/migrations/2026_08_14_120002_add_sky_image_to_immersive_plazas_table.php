<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido del usuario: imagen de cielo (spheremap/equirectangular) por
     * plaza, editable desde "Editar Plaza" — lo mismo que tenían las
     * plazas hardcodeadas de Zipaquirá/Cajicá (imagen fija en
     * `public/3D/`, cargada a mano con `EquirectangularReflectionMapping`
     * sobre un domo esférico) pero ahora configurable por plaza y sin
     * geometría de domo hardcodeada: se aplica directo como
     * `scene.background`. Nula (plaza sin configurar) preserva el
     * comportamiento actual — el color de cielo plano de
     * `VoxelPlazaEngine`.
     */
    public function up(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->string('sky_image_path')->nullable()->after('legend_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plazas', function (Blueprint $table) {
            $table->dropColumn('sky_image_path');
        });
    }
};
