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
            $table->string('screen_material_name')->nullable()->after('model_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table) {
            $table->dropColumn('screen_material_name');
        });
    }
};
