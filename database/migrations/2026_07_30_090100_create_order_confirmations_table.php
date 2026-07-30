<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('business_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('source');
            $table->enum('status', ['pendiente_confirmacion', 'confirmado_por_ambos', 'completado', 'cancelado', 'en_disputa'])
                ->default('pendiente_confirmacion');
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->timestamp('business_confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('summary')->nullable();
            $table->text('dispute_note')->nullable();
            $table->boolean('is_reputation_eligible')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmations');
    }
};
