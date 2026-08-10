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
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            // IMM-020b: cuando el objeto no coincide con ningún builder_key
            // escrito a mano, la IA genera esta definición — una lista de
            // cajas voxel (mismo vocabulario que addVoxelBox en
            // voxel-plaza-engine.js) que un builder genérico (buildFromDefinition)
            // sabe interpretar sin que un desarrollador escriba código nuevo.
            $table->json('model_definition')->nullable()->after('builder_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->dropColumn('model_definition');
        });
    }
};
