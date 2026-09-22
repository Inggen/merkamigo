<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('description');
            $table->string('stream_path')->nullable()->unique()->after('cover_path');
            $table->text('stream_key')->nullable()->after('stream_path');
            $table->string('stream_origin', 30)->default('legacy')->after('stream_key');
            $table->string('signal_status', 30)->default('offline')->after('stream_key');
            $table->timestamp('scheduled_at')->nullable()->after('status');
        });

        Schema::create('live_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('name', 80);
            $table->text('rtmp_url');
            $table->text('stream_key');
            $table->boolean('is_enabled')->default(true);
            $table->string('status', 30)->default('disconnected');
            $table->text('last_error')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();

            $table->index(['live_stream_id', 'is_enabled']);
        });

        Schema::create('live_product_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['featured', 'unfeatured']);
            $table->unsignedInteger('elapsed_seconds')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['live_stream_id', 'elapsed_seconds']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('live_stream_id')->nullable()->after('content_promotion_id')->constrained()->nullOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('amount_cents');
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('live_stream_id');
        });

        Schema::dropIfExists('live_product_events');
        Schema::dropIfExists('live_destinations');

        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropUnique(['stream_path']);
            $table->dropColumn(['cover_path', 'stream_path', 'stream_key', 'stream_origin', 'signal_status', 'scheduled_at']);
        });
    }
};
