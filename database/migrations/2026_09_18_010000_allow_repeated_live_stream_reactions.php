<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_reactions', function (Blueprint $table) {
            $table->index(['live_stream_id', 'id'], 'live_stream_reactions_stream_event_index');
        });

        Schema::table('live_stream_reactions', function (Blueprint $table) {
            $table->dropUnique(['live_stream_id', 'visitor_hash', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_reactions', function (Blueprint $table) {
            $table->dropIndex('live_stream_reactions_stream_event_index');
        });
    }
};
