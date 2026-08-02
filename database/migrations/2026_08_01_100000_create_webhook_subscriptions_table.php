<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            // null = suscripción de plataforma (recibe el evento sin
            // importar a qué negocio pertenezca); con business_id, solo
            // recibe eventos de ese negocio.
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret');
            $table->json('subscribed_events');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
