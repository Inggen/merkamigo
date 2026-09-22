<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cobro periódico de la comisión de Merkamigo por las ventas de un
     * negocio (decisión del usuario: se cobra aparte contra la tarjeta ya
     * guardada para la suscripción — reutiliza `payments`/`WompiClient`
     * existentes, `payment_id` apunta al `Payment` real que se generó
     * para cobrarla).
     */
    public function up(): void
    {
        Schema::create('commission_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedBigInteger('gross_amount_cents')->default(0);
            $table->unsignedBigInteger('commission_cents')->default(0);
            $table->enum('status', ['abierta', 'pendiente_cobro', 'pagada', 'fallida'])->default('abierta');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_charges');
    }
};
