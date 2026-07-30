<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('need_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('availability')->nullable();
            $table->string('status')->default('enviada');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // Un negocio no puede enviar dos propuestas activas a la misma
            // necesidad (2.2 del TODO: limitar número de propuestas) — la
            // única forma de "reenviar" es retirar la anterior primero.
            $table->unique(['need_id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
