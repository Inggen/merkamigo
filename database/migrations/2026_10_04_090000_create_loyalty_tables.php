<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merkapuntos / Merkamigo Premia — TODO_Merkapuntos.md, Fase 1 (F1.1).
 *
 * Simplificación deliberada frente al TODO: "Reserva de presupuesto" no es
 * una tabla propia. Se modela como columnas agregadas en `loyalty_rewards`
 * (`budget_reserved_cents`/`budget_spent_cents`), protegidas con
 * `lockForUpdate()` en `ReserveLoyaltyRedemption` — mismo efecto
 * (compromiso atómico de presupuesto por premio) con un modelo más simple
 * para un MVP de un solo negocio por premio. "Campaña" (bonos globales,
 * F4.4) no se crea todavía: esa fase está explícitamente bloqueada hasta
 * que exista un modelo de financiación aprobado.
 *
 * Todo lo nuevo es aditivo: no se toca ninguna tabla existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            // pendiente: formulario guardado pero sin política activa todavía.
            // activa: tiene política vigente y puede operar el escáner.
            // suspendida: temporalmente fuera (ej. impago), conserva historial.
            // retirada: adhesión terminada por el negocio, canjes pendientes se resuelven aparte.
            $table->string('status', 20)->default('pendiente');
            $table->string('consent_version', 20);
            $table->timestamp('consented_at');
            $table->foreignId('responsible_user_id')->constrained('users');
            $table->unsignedBigInteger('budget_cents')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('loyalty_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            // borrador: en edición, nunca usada para acreditar.
            // activa: única vigente por negocio en un momento dado.
            // archivada: reemplazada por una versión nueva, se conserva para los snapshots existentes.
            $table->string('status', 20)->default('borrador');
            // Regla determinista de acumulación, ej.:
            // {"type":"simple","points_per_unit":1,"unit_cents":100000}
            // Reglas de producto: "Antes del lanzamiento definir moneda,
            // impuestos, descuentos, rubros excluidos, base elegible,
            // devoluciones y redondeo" — ese detalle vive en `eligible_base_notes`,
            // texto operativo revisado por el negocio, no inferido por código.
            $table->json('rule');
            $table->text('eligible_base_notes')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['business_id', 'version']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('loyalty_identity_tokens', function (Blueprint $table) {
            $table->id();
            // F1.8: identifica al cliente, nunca autoriza retiro de puntos.
            // Por usuario (no por negocio): el mismo QR sirve en cualquier
            // vitrina adherida, el negocio solo mira su propia cuenta.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('rotated_at');
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('activa'); // activa | suspendida
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('loyalty_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            // acumulacion | reserva_canje | consumo_canje | liberacion_canje | reversion | ajuste
            $table->string('type', 24);
            $table->integer('points'); // con signo: acumulación/liberación positivos, reserva/consumo negativos
            $table->nullableMorphs('reference'); // Compra, Canje, SolicitudExcepcional, etc.
            $table->string('idempotency_key', 80)->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
        });

        Schema::create('loyalty_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users');
            $table->unsignedBigInteger('eligible_amount_cents');
            $table->string('origin', 20)->default('presencial'); // presencial | pedido
            $table->string('external_reference', 120)->nullable();
            $table->string('status', 20)->default('registrada'); // registrada | revertida
            $table->foreignId('policy_id')->constrained('loyalty_policies');
            $table->json('policy_snapshot');
            $table->unsignedInteger('points_awarded');
            $table->foreignId('employee_user_id')->constrained('users');
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();

            // F1.4: recibo externo único por negocio cuando existe. NULL no
            // colisiona consigo mismo en un índice único (MySQL/Postgres),
            // así que las compras presenciales sin referencia conviven.
            $table->unique(['business_id', 'external_reference']);
            $table->index(['business_id', 'customer_user_id', 'created_at']);
        });

        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('producto'); // producto | descuento | regalo
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->unsignedBigInteger('full_cost_cents');
            $table->unsignedInteger('stock_total')->nullable(); // null = sin límite de unidades
            $table->unsignedInteger('stock_reserved')->default(0);
            $table->unsignedInteger('stock_delivered')->default(0);
            $table->unsignedBigInteger('max_budget_cents')->nullable(); // null = sin tope propio (usa el del negocio)
            $table->unsignedBigInteger('budget_reserved_cents')->default(0);
            $table->unsignedBigInteger('budget_spent_cents')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            // borrador | publicado | pausado | agotado | archivado
            $table->string('status', 20)->default('borrador');
            $table->unsignedInteger('version')->default(1);
            $table->text('terms')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('loyalty_rewards');
            // Copia de título/costo/condiciones al momento de reservar — F1.5/F1.6:
            // "No cambiar requisitos de un canje ya reservado."
            $table->json('reward_snapshot');
            $table->unsignedInteger('points_reserved');
            $table->unsignedBigInteger('cost_reserved_cents');
            $table->string('token_hash', 64)->unique();
            // reservado | entregado | cancelado | expirado
            $table->string('status', 20)->default('reservado');
            $table->foreignId('delivered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->string('cancelled_reason', 60)->nullable();
            $table->timestamp('expires_at');
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('loyalty_receipt_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users');
            // Disco privado (nunca `public`) — ver `App\Support\Media\MediaUploader`.
            $table->string('receipt_path');
            $table->text('description')->nullable();
            // pendiente | vinculada | aprobada | rechazada
            $table->string('status', 20)->default('pendiente');
            $table->foreignId('linked_purchase_id')->nullable()->constrained('loyalty_purchases')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_receipt_claims');
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_purchases');
        Schema::dropIfExists('loyalty_movements');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('loyalty_identity_tokens');
        Schema::dropIfExists('loyalty_policies');
        Schema::dropIfExists('loyalty_enrollments');
    }
};
