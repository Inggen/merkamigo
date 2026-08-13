<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capacidades desbloqueadas por negocio (ej. el chatbot con IA de la
     * vitrina), otorgadas por un `BillingProduct` de un solo pago (kind
     * `entitlement`) o revisadas junto al plan activo del negocio.
     * `expires_at` nulo significa desbloqueo permanente; una fecha futura
     * significa que vence (igual que `businesses.featured_until`, pero
     * genérico por clave para poder agregar más add-ons sin tablas nuevas.
     */
    public function up(): void
    {
        Schema::create('business_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->foreignId('source_billing_product_id')->nullable()->constrained('billing_products')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_entitlements');
    }
};
