<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->string('hero_video_path')->nullable()->after('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropColumn('hero_video_path');
        });
    }
};
