<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventDish;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Events\Models\EventSetting;
use App\Domain\Events\Models\EventSpace;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Crea una reserva privada de evento con cotización en servidor
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3). Mismo
 * protocolo de bloqueo que `ReserveLoyaltyRedemption`: `lockForUpdate()`
 * sobre el espacio ANTES de revisar solapamientos, así dos prospectos no
 * pueden confirmar el mismo cupo al mismo tiempo. Idempotente por
 * `idempotencyKey` — un reintento de red no crea una segunda reserva.
 */
class CreateEventReservation
{
    /**
     * @param  array<int, array{dish_id: int, quantity: int}>  $dishes
     * @param  array<int, array{equipment_id: int, quantity: int}>  $equipment
     *
     * @throws EventActionException
     */
    public function handle(
        Business $business,
        EventSpace $space,
        CarbonInterface $startsAt,
        int $durationHours,
        int $partySize,
        array $dishes,
        array $equipment,
        string $prospectName,
        string $prospectEmail,
        string $prospectPhone,
        ?User $customer,
        string $idempotencyKey,
    ): EventReservation {
        $existing = EventReservation::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        $settings = $business->eventSetting;

        if (! $settings?->enabled || ! $settings->private_reservations_enabled) {
            throw new EventActionException('Este negocio no acepta reservas de eventos en este momento.');
        }

        if (! $business->hasWompiConnected()) {
            throw new EventActionException('Este negocio todavía no puede recibir pagos de eventos.');
        }

        if ($space->business_id !== $business->id || ! $space->is_active) {
            throw new EventActionException('Este espacio ya no está disponible.');
        }

        if ($durationHours < 1 || $durationHours > $settings->max_duration_hours) {
            throw new EventActionException("La duración debe ser entre 1 y {$settings->max_duration_hours} horas.");
        }

        if ($startsAt->lt(now()->addHours($settings->min_advance_hours))) {
            throw new EventActionException("Reserva con al menos {$settings->min_advance_hours} horas de anticipación.");
        }

        $endsAt = $startsAt->copy()->addHours($durationHours);

        // La validación de horario y fecha bloqueada se hace en el huso
        // del NEGOCIO (coincide con el día/hora que ve su dueño y con
        // "10:00"-"22:00" de `weekly_schedule`) — todavía con $startsAt
        // tal como llegó, antes de normalizar.
        $this->assertWithinSchedule($settings, $startsAt, $endsAt);

        if ($business->eventBlockedDates()->whereDate('date', $startsAt->toDateString())->exists()) {
            throw new EventActionException('Esa fecha no está disponible para eventos.');
        }

        // Fase 7 ("pruebas... de fechas y zona horaria"): la columna
        // `starts_at`/`ends_at` es un DATETIME sin zona horaria propia —
        // si se guardara a secas lo que llega en el huso del negocio
        // (ej. Europe/Madrid), al releerla Eloquent la reinterpretaría
        // en el huso de la aplicación y el instante real cambiaría. Se
        // normaliza aquí, UNA sola vez, después de validar horario
        // (que si necesita el huso del negocio) y antes de guardar o
        // comparar contra lo ya guardado — así toda reserva en la tabla
        // queda en el mismo huso de referencia.
        $startsAt = $startsAt->copy()->setTimezone(config('app.timezone'));
        $endsAt = $endsAt->copy()->setTimezone(config('app.timezone'));

        if ($partySize < 1) {
            throw new EventActionException('Indica al menos una persona.');
        }

        $capacityLimits = array_filter([$space->capacity, $settings->max_capacity], fn ($v) => $v !== null);
        $maxCapacity = $capacityLimits === [] ? null : min($capacityLimits);
        if ($maxCapacity && $partySize > $maxCapacity) {
            throw new EventActionException("Este espacio admite máximo {$maxCapacity} personas.");
        }

        $dishSelections = $this->resolveDishSelections($business, $dishes);
        $equipmentSelections = $this->resolveEquipmentSelections($business, $equipment);

        if (in_array($settings->pricing_mode, ['platos', 'hibrido'], true) && collect($dishSelections)->sum('quantity') < 1) {
            throw new EventActionException('Selecciona al menos un plato.');
        }

        $quote = app(QuoteEventReservation::class)->handle($settings, $durationHours, $dishSelections, $equipmentSelections);

        if ($quote['total_cents'] <= 0) {
            throw new EventActionException('El total de la reserva debe ser mayor que cero.');
        }

        return DB::transaction(function () use (
            $business, $space, $startsAt, $endsAt, $durationHours, $partySize,
            $dishSelections, $equipmentSelections, $quote, $settings,
            $prospectName, $prospectEmail, $prospectPhone, $customer, $idempotencyKey,
        ): EventReservation {
            // Mismo protocolo que `ReserveLoyaltyRedemption`: bloquear el
            // recurso disputado (el espacio) ANTES de leer solapamientos.
            EventSpace::whereKey($space->id)->lockForUpdate()->firstOrFail();

            $overlaps = EventReservation::where('event_space_id', $space->id)
                ->where(function ($q) {
                    $q->where('status', EventReservation::CONFIRMADA)
                        ->orWhere(function ($q2) {
                            $q2->where('status', EventReservation::PENDIENTE_PAGO)->where('expires_at', '>', now());
                        });
                })
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($overlaps) {
                throw new EventActionException('Ese horario acaba de ser reservado por alguien más. Elige otro.');
            }

            if ($this->blockedByPublicEvent($space, $startsAt, $endsAt)) {
                throw new EventActionException('Ese espacio está ocupado por un evento público en ese horario.');
            }

            $reservation = EventReservation::create([
                'business_id' => $business->id,
                'event_space_id' => $space->id,
                'customer_user_id' => $customer?->id,
                'prospect_name' => $prospectName,
                'prospect_email' => $prospectEmail,
                'prospect_phone' => $prospectPhone,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_hours' => $durationHours,
                'party_size' => $partySize,
                'pricing_snapshot' => [
                    'pricing_mode' => $settings->pricing_mode,
                    'hourly_rate_cents' => $settings->hourly_rate_cents,
                ],
                'dishes_total_cents' => $quote['dishes_total_cents'],
                'space_total_cents' => $quote['space_total_cents'],
                'equipment_total_cents' => $quote['equipment_total_cents'],
                'total_cents' => $quote['total_cents'],
                'status' => EventReservation::PENDIENTE_PAGO,
                'expires_at' => now()->addMinutes($settings->hold_minutes),
                'terms_accepted_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($dishSelections as $selection) {
                if ($selection['quantity'] <= 0) {
                    continue;
                }

                $reservation->dishes()->create([
                    'event_dish_id' => $selection['dish']->id,
                    'name_snapshot' => $selection['dish']->name,
                    'unit_price_cents_snapshot' => $selection['dish']->price_cents,
                    'quantity' => $selection['quantity'],
                ]);
            }

            foreach ($equipmentSelections as $selection) {
                if ($selection['quantity'] <= 0) {
                    continue;
                }

                $reservation->equipment()->create([
                    'event_business_equipment_id' => $selection['equipment']->id,
                    'name_snapshot' => $selection['equipment']->displayName(),
                    'fee_cents_snapshot' => $selection['equipment']->fee_cents ?? 0,
                    'quantity' => $selection['quantity'],
                ]);
            }

            app(CreateEventPaymentAttempt::class)->handle($reservation);

            // Fase 7: "auditar cambios sensibles" — deja rastro de cada
            // intento de reserva, no solo de los que terminan pagados;
            // el actor puede ser null (invitado sin cuenta).
            app(RecordAuditLog::class)->handle($customer, 'event_reservation.created', $reservation, [
                'business_id' => $business->id,
                'total_cents' => $quote['total_cents'],
            ]);

            return $reservation;
        });
    }

    /**
     * Fase 5: un evento público marcado `blocks_space` ocupa el espacio
     * igual que una reserva privada confirmada — se revisa dentro del
     * mismo bloqueo transaccional del espacio, así que es tan seguro
     * frente a condiciones de carrera como el chequeo de solapamiento de
     * reservas de arriba.
     */
    private function blockedByPublicEvent(EventSpace $space, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        return PublicEvent::where('event_space_id', $space->id)
            ->where('blocks_space', true)
            ->where('status', PublicEvent::PUBLICADO)
            ->where('starts_at', '<', $endsAt)
            ->get(['starts_at', 'ends_at'])
            ->contains(fn (PublicEvent $event) => $event->effectiveEndsAt()->gt($startsAt));
    }

    private function assertWithinSchedule(EventSetting $settings, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        $dayKey = strtolower($startsAt->format('l'));
        $day = $settings->weekly_schedule[$dayKey] ?? null;

        if (! $day || $day['closed'] || empty($day['open']) || empty($day['close'])) {
            throw new EventActionException('El negocio no recibe eventos ese día.');
        }

        $openAt = $startsAt->copy()->setTimeFromTimeString($day['open']);
        $closeAt = $startsAt->copy()->setTimeFromTimeString($day['close']);

        if ($startsAt->lt($openAt) || $endsAt->gt($closeAt)) {
            throw new EventActionException('Ese horario está fuera del horario de atención para eventos.');
        }
    }

    /**
     * @param  array<int, array{dish_id: int, quantity: int}>  $dishes
     * @return array<int, array{dish: EventDish, quantity: int}>
     */
    private function resolveDishSelections(Business $business, array $dishes): array
    {
        $selections = [];

        foreach ($dishes as $entry) {
            $quantity = $entry['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $dish = $business->eventDishes()->where('is_available', true)->find($entry['dish_id']);

            if (! $dish) {
                throw new EventActionException('Uno de los platos seleccionados ya no está disponible.');
            }

            $selections[] = ['dish' => $dish, 'quantity' => $quantity];
        }

        return $selections;
    }

    /**
     * @param  array<int, array{equipment_id: int, quantity: int}>  $equipment
     * @return array<int, array{equipment: EventBusinessEquipment, quantity: int}>
     */
    private function resolveEquipmentSelections(Business $business, array $equipment): array
    {
        $selections = [];

        foreach ($equipment as $entry) {
            $quantity = $entry['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $item = $business->eventEquipment()->where('is_active', true)->find($entry['equipment_id']);

            if (! $item) {
                throw new EventActionException('Uno de los equipos seleccionados ya no está disponible.');
            }

            if ($item->quantity_available !== null && $quantity > $item->quantity_available) {
                throw new EventActionException("Solo hay {$item->quantity_available} unidades disponibles de \"{$item->displayName()}\".");
            }

            $selections[] = ['equipment' => $item, 'quantity' => $quantity];
        }

        return $selections;
    }
}
