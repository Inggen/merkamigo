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
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            // Nulo = usa el default del motor (8s) — ver `billboard-ad-utils.js`.
            $table->unsignedInteger('ad_rotation_seconds')->nullable()->after('collision_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('ad_rotation_seconds');
        });
    }
};
