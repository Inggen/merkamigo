<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_stream_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('question', 120);
            $table->json('options');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_stream_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64);
            $table->unsignedTinyInteger('option_index');
            $table->timestamps();

            $table->unique(['live_stream_poll_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_poll_votes');
        Schema::dropIfExists('live_stream_polls');
    }
};
