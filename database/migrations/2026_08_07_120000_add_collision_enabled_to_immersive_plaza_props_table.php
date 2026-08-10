<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->boolean('collision_enabled')
                ->default(false)
                ->after('scale_vector');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('collision_enabled');
        });
    }
};
