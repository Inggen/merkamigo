<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos y reservas (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase
 * 1). Todo aditivo: no se toca ninguna tabla existente. El esquema admite
 * varios espacios por negocio desde el inicio (reglas de producto: "el
 * esquema debe admitir varios sin rediseño"), aunque el MVP solo exponga
 * uno en la UI.
 *
 * Simplificación deliberada: el horario semanal vive como JSON en
 * `event_settings.weekly_schedule` en vez de una tabla aparte — mismo
 * criterio que `loyalty_policies.rule` en Merkapuntos, una estructura
 * fija y pequeña (7 días) no necesita su propia tabla. Las fechas
 * bloqueadas sí son su propia tabla (`event_blocked_dates`) porque crecen
 * sin límite fijo y se consultan por fecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->boolean('public_events_enabled')->default(false);
            $table->boolean('private_reservations_enabled')->default(false);
            $table->string('timezone', 64)->default('America/Bogota');
            // producto, horas o híbrido — ver reglas de producto, Fase 3.
            $table->string('pricing_mode', 20)->default('platos');
            $table->unsignedBigInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->unsignedInteger('min_advance_hours')->default(24);
            // Reglas de producto: "duración máxima de tres horas" — límite
            // configurable por negocio, nunca superior a lo que el propio
            // negocio decida (y nunca forzado a exactamente 3 en código).
            $table->unsignedInteger('max_duration_hours')->default(3);
            $table->unsignedInteger('hold_minutes')->default(30);
            // Horario semanal: {"monday": {"closed": false, "open": "10:00", "close": "22:00"}, ...}
            // mismas claves que `Business::DAY_LABELS`, para reutilizar el
            // mismo patrón de edición que ya existe en el horario general
            // de la vitrina (⚡vitrina.blade.php) en vez de inventar uno
            // paralelo.
            $table->json('weekly_schedule')->nullable();
            $table->text('policy_text')->nullable();
            $table->text('cancellation_text')->nullable();
            $table->timestamps();
        });

        Schema::create('event_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'date']);
        });

        Schema::create('event_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        Schema::create('event_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_cents');
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'is_available']);
        });

        // Catálogo GLOBAL de equipos (administrador de la plataforma). Un
        // negocio selecciona de aquí en `event_business_equipment`; nunca
        // edita estas filas directamente.
        Schema::create('event_equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Selección del negocio: un tipo del catálogo global, o uno propio
        // ("Otro") con `custom_name` — nunca modifica el catálogo global.
        Schema::create('event_business_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_equipment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('fee_cents')->nullable();
            $table->unsignedInteger('quantity_available')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        // Fase 5: eventos públicos (agenda). `event_space_id` solo si el
        // evento bloquea disponibilidad — reglas de producto: "una
        // publicación informativa no bloquea disponibilidad por defecto".
        Schema::create('public_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_space_id')->nullable()->constrained('event_spaces')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            // Único global (no solo por negocio): la agenda pública es un
            // solo listado compartido y el detalle se liga por slug
            // (`{publicEvent:slug}`), igual que `LiveStream`.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('location_text')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('blocks_space')->default(false);
            // borrador | publicado | finalizado | cancelado
            $table->string('status', 20)->default('borrador');
            $table->foreignId('created_by_user_id')->constrained('users');
            // Post del feed creado al publicar (Fase 5) — impide publicar
            // dos veces el mismo evento.
            $table->unsignedBigInteger('post_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['municipality_id', 'status', 'starts_at']);
        });

        // Fase 3: reserva privada de un espacio. El prospecto puede no
        // tener cuenta ("puedes explorar y reservar sin registrarte" en
        // la referencia) — por eso los datos de contacto van aparte de
        // `customer_user_id`, que es opcional.
        Schema::create('event_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_space_id')->constrained('event_spaces');
            $table->foreignId('public_event_id')->nullable()->constrained('public_events')->nullOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prospect_name');
            $table->string('prospect_email');
            $table->string('prospect_phone', 32);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('duration_hours');
            $table->unsignedInteger('party_size');
            // Copia de `event_settings` al momento de cotizar — reglas de
            // producto: "no cambiar requisitos de un canje ya reservado"
            // (mismo principio que Merkapuntos, aplicado aquí a reservas).
            $table->json('pricing_snapshot');
            $table->unsignedBigInteger('dishes_total_cents')->default(0);
            $table->unsignedBigInteger('space_total_cents')->default(0);
            $table->unsignedBigInteger('equipment_total_cents')->default(0);
            $table->unsignedBigInteger('total_cents');
            // pendiente_pago | confirmada | pago_fallido | vencida | cancelada
            $table->string('status', 20)->default('pendiente_pago');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->text('cancellation_reason')->nullable();
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();

            $table->index(['business_id', 'event_space_id', 'status', 'starts_at'], 'event_reservations_slot_lookup_index');
            $table->index(['status', 'expires_at']);
        });

        Schema::create('event_reservation_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_reservation_id')->constrained('event_reservations')->cascadeOnDelete();
            $table->foreignId('event_dish_id')->nullable()->constrained('event_dishes')->nullOnDelete();
            $table->string('name_snapshot');
            $table->unsignedBigInteger('unit_price_cents_snapshot');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('event_reservation_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_reservation_id')->constrained('event_reservations')->cascadeOnDelete();
            $table->foreignId('event_business_equipment_id')->nullable()->constrained('event_business_equipment')->nullOnDelete();
            $table->string('name_snapshot');
            $table->unsignedBigInteger('fee_cents_snapshot')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        // Mismo patrón que `loyalty_redemptions` para idempotencia de
        // pago: una referencia única por intento, nunca se reutiliza.
        Schema::create('event_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_reservation_id')->constrained('event_reservations')->cascadeOnDelete();
            $table->string('reference', 120)->unique();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('COP');
            $table->string('status', 20)->default('pendiente');
            $table->string('wompi_transaction_id')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['event_reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_payment_attempts');
        Schema::dropIfExists('event_reservation_equipment');
        Schema::dropIfExists('event_reservation_dishes');
        Schema::dropIfExists('event_reservations');
        Schema::dropIfExists('public_events');
        Schema::dropIfExists('event_business_equipment');
        Schema::dropIfExists('event_equipment_types');
        Schema::dropIfExists('event_dishes');
        Schema::dropIfExists('event_spaces');
        Schema::dropIfExists('event_blocked_dates');
        Schema::dropIfExists('event_settings');
    }
};
