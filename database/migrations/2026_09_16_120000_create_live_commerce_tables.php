<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pinned_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('title', 120);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('provider', ['youtube', 'vimeo', 'externo'])->default('youtube');
            $table->text('stream_url');
            $table->text('replay_url')->nullable();
            $table->enum('status', ['borrador', 'en_vivo', 'finalizado'])->default('borrador');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['status', 'started_at']);
        });

        Schema::create('live_stream_product', function (Blueprint $table) {
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->primary(['live_stream_id', 'product_id']);
        });

        Schema::create('live_stream_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['live_stream_id', 'visitor_hash']);
            $table->index(['live_stream_id', 'last_seen_at']);
        });

        Schema::create('live_stream_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 280);
            $table->enum('status', ['publicado', 'oculto'])->default('publicado');
            $table->timestamps();

            $table->index(['live_stream_id', 'status', 'created_at']);
        });

        Schema::create('live_stream_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('type', 20)->default('heart');
            $table->timestamps();

            $table->unique(['live_stream_id', 'visitor_hash', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_reactions');
        Schema::dropIfExists('live_stream_messages');
        Schema::dropIfExists('live_stream_views');
        Schema::dropIfExists('live_stream_product');
        Schema::dropIfExists('live_streams');
    }
};
