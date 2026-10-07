<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Events\Notifications\EventAttendanceConfirmed;
use App\Domain\Events\Notifications\EventAttendanceConfirmedForAttendee;
use App\Domain\Loyalty\Support\LoyaltyTokens;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Reserva de CUPO a un evento público (pedido explícito del usuario,
 * 2026-10-08) — distinta de `CreateEventReservation` (reserva el
 * ESPACIO del negocio para un evento privado propio). "Si por ejemplo es
 * un taller, se debe poder hacer la reserva según el cupo y
 * disponibilidad del evento... si es sin pago, el usuario solo hace la
 * reserva, se le genera un qr para la entrada."
 *
 * Mismo protocolo de bloqueo que `ReserveLoyaltyRedemption`/
 * `CreateEventReservation`: `lockForUpdate()` sobre el recurso disputado
 * (el evento, dueño del cupo) ANTES de contar cuánto cupo queda —
 * idempotente por `idempotencyKey`.
 */
class CreateEventAttendance
{
    public function handle(
        PublicEvent $event,
        int $quantity,
        string $attendeeName,
        string $attendeeEmail,
        string $attendeePhone,
        ?User $customer,
        string $idempotencyKey,
    ): EventAttendance {
        $existing = EventAttendance::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        if ($event->status !== PublicEvent::PUBLICADO) {
            throw new EventActionException('Este evento no está disponible para reservar cupo.');
        }

        if ($event->isPast()) {
            throw new EventActionException('Este evento ya pasó.');
        }

        if ($quantity < 1) {
            throw new EventActionException('Indica al menos un cupo.');
        }

        $unitPriceCents = $event->price_cents ?? 0;
        $totalCents = $unitPriceCents * $quantity;

        return DB::transaction(function () use ($event, $quantity, $attendeeName, $attendeeEmail, $attendeePhone, $customer, $idempotencyKey, $unitPriceCents, $totalCents) {
            // Bloquea el evento (dueño del cupo) ANTES de contar cuánto
            // queda — así dos prospectos no pueden agotar el último
            // cupo al mismo tiempo.
            /** @var PublicEvent $locked */
            $locked = PublicEvent::whereKey($event->id)->lockForUpdate()->firstOrFail();

            $remaining = $locked->spotsRemaining();

            if ($remaining !== null && $quantity > $remaining) {
                throw new EventActionException(
                    $remaining > 0
                        ? "Solo quedan {$remaining} cupos disponibles."
                        : 'Ya no quedan cupos disponibles para este evento.',
                );
            }

            $isFree = $totalCents <= 0;
            // `LoyaltyTokens` es un generador genérico de token+hash (sin
            // nada específico de Loyalty) ya usado para códigos que no
            // se guardan en claro — se reutiliza tal cual en vez de
            // duplicarlo, con un prefijo propio de Eventos.
            ['token' => $token, 'hash' => $hash] = LoyaltyTokens::generate('evt');

            $attendance = EventAttendance::create([
                'public_event_id' => $locked->id,
                'business_id' => $locked->business_id,
                'customer_user_id' => $customer?->id,
                'attendee_name' => $attendeeName,
                'attendee_email' => $attendeeEmail,
                'attendee_phone' => $attendeePhone,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'total_cents' => $totalCents,
                'status' => $isFree ? EventAttendance::CONFIRMADA : EventAttendance::PENDIENTE_PAGO,
                'expires_at' => $isFree ? null : now()->addMinutes((int) config('events.attendance_hold_minutes')),
                'checkin_token_hash' => $hash,
                'checkin_token_encrypted' => $token,
                'idempotency_key' => $idempotencyKey,
            ]);

            app(RecordAuditLog::class)->handle($customer, 'event_attendance.created', $attendance, [
                'business_id' => $locked->business_id,
                'public_event_id' => $locked->id,
                'quantity' => $quantity,
            ]);

            if ($isFree) {
                $this->notifyConfirmed($attendance);
            } else {
                app(CreateEventAttendancePaymentAttempt::class)->handle($attendance);
            }

            return $attendance;
        });
    }

    public function notifyConfirmed(EventAttendance $attendance): void
    {
        $attendance->business->members->each(fn ($member) => $member->notify(new EventAttendanceConfirmed($attendance)));

        Notification::route('mail', $attendance->attendee_email)
            ->notify(new EventAttendanceConfirmedForAttendee($attendance));
    }
}
