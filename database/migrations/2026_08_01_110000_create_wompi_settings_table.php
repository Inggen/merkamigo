<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fila única (singleton): guarda las credenciales de sandbox y de
     * producción a la vez, con `active_env` decidiendo cuál usa
     * `WompiClient` — cambiar de sandbox a producción es un clic en el
     * admin, no una variable de entorno ni un despliegue.
     */
    public function up(): void
    {
        Schema::create('wompi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_env')->default('sandbox');

            $table->string('sandbox_public_key')->nullable();
            $table->string('sandbox_private_key')->nullable();
            $table->string('sandbox_integrity_secret')->nullable();
            $table->string('sandbox_events_secret')->nullable();

            $table->string('production_public_key')->nullable();
            $table->string('production_private_key')->nullable();
            $table->string('production_integrity_secret')->nullable();
            $table->string('production_events_secret')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wompi_settings');
    }
};
