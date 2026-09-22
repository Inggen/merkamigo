<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->string('intro_video_path')->nullable()->after('cover_path');
            $table->boolean('intro_video_active')->default(false)->after('intro_video_path');
        });
    }

    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn(['intro_video_path', 'intro_video_active']);
        });
    }
};
