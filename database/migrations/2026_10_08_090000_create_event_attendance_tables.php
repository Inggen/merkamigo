<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reserva de CUPO/asistencia a un evento público — distinta de
 * `event_reservations` (que reserva el ESPACIO del negocio para un
 * evento privado propio). Pedido explícito del usuario: "si por ejemplo
 * es un taller, se debe poder hacer la reserva según el cupo y
 * disponibilidad del evento... si es sin pago, el usuario solo hace la
 * reserva, se le genera un qr para la entrada (en la zona de
 * emprendedores debe poderse leer ese qr para verificar la asistencia)".
 *
 * Mismo patrón de token que `loyalty_redemptions` (F1.6 de
 * TODO_Merkapuntos.md): `checkin_token_hash` para buscar sin guardar el
 * código en claro, `checkin_token_encrypted` (cast `encrypted`,
 * reversible) para poder volver a mostrar el mismo QR si el asistente
 * reabre la página. Todo aditivo, ninguna tabla existente se toca salvo
 * agregar `price_cents` a `public_events` (nullable, null/0 = evento
 * gratuito).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarda defensiva: en este entorno de desarrollo la columna ya
        // existía sin una migración propia que la describiera (drift de
        // esquema) — se agrega aquí "de verdad" para que cualquier otro
        // entorno (producción, un setup nuevo) quede igual, sin fallar
        // si ya está presente.
        if (! Schema::hasColumn('public_events', 'price_cents')) {
            Schema::table('public_events', function (Blueprint $table) {
                $table->unsignedBigInteger('price_cents')->nullable()->after('capacity');
            });
        }

        if (! Schema::hasTable('event_attendances')) {
            Schema::create('event_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('public_event_id')->constrained('public_events')->cascadeOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('attendee_name');
                $table->string('attendee_email');
                $table->string('attendee_phone', 32);
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedBigInteger('unit_price_cents')->default(0);
                $table->unsignedBigInteger('total_cents')->default(0);
                // pendiente_pago | confirmada | pago_fallido | vencida | cancelada
                $table->string('status', 20)->default('confirmada');
                $table->timestamp('expires_at')->nullable();
                $table->string('checkin_token_hash', 64)->unique();
                $table->text('checkin_token_encrypted');
                $table->timestamp('checked_in_at')->nullable();
                $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('idempotency_key', 80)->unique();
                $table->timestamps();

                $table->index(['public_event_id', 'status']);
                $table->index(['business_id', 'status']);
                $table->index(['status', 'expires_at']);
            });
        }

        if (! Schema::hasTable('event_attendance_payment_attempts')) {
            Schema::create('event_attendance_payment_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_attendance_id')->constrained('event_attendances')->cascadeOnDelete();
                $table->string('reference', 120)->unique();
                $table->unsignedBigInteger('amount_cents');
                $table->string('currency', 3)->default('COP');
                $table->string('status', 20)->default('pendiente');
                $table->string('wompi_transaction_id')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamps();

                $table->index(['event_attendance_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance_payment_attempts');
        Schema::dropIfExists('event_attendances');

        Schema::table('public_events', function (Blueprint $table) {
            $table->dropColumn('price_cents');
        });
    }
};
