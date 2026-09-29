<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->boolean('show_posts')->default(true)->after('stand_color');
            $table->boolean('show_reels')->default(true)->after('show_posts');
            $table->text('google_maps_embed_url')->nullable()->after('show_reels');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('is_highlighted')->default(false)->after('views_count');
            $table->index(['business_id', 'is_highlighted']);
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_highlighted']);
            $table->dropColumn('is_highlighted');
        });

        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn(['show_posts', 'show_reels', 'google_maps_embed_url']);
        });
    }
};
