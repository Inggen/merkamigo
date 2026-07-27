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
        Schema::create('business_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // El rol (owner/admin/collaborator) vive en las tablas de
            // spatie/laravel-permission usando business_id como "team", no
            // aquí, para no duplicar el almacenamiento del rol (ver
            // docs/architecture/decisiones.md).
            $table->enum('status', ['invitado', 'activo', 'revocado'])->default('activo');
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_memberships');
    }
};
