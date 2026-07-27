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
        Schema::table('storefronts', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('description');
            // Momento real de publicación, para "tiempo hasta publicar"
            // (0.1 y 1.8 del TODO) sin necesitar otra migración después.
            $table->timestamp('published_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'published_at']);
        });
    }
};
