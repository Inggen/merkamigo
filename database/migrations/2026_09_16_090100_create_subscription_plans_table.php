<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de suscripción de UN producto de un negocio a SUS clientes (Fase
 * 8.1 del TODO social) — un plan por producto en este alcance (sin
 * niveles/tiers todavía).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('frequency', ['semanal', 'mensual', 'trimestral', 'anual']);
            $table->unsignedSmallInteger('trial_days')->nullable();
            $table->text('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
