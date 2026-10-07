<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Events\Models\EventSetting;
use App\Domain\Events\Models\EventSpace;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Support\Carbon;

/**
 * Horas de inicio candidatas para el cotizador público (Fase 3, paso 1):
 * solo apoyo de UI — cada hora propuesta todavía se revalida de forma
 * autoritativa (con bloqueo de fila) en `CreateEventReservation` al
 * confirmar, así que una condición de carrera aquí nunca permite una
 * doble reserva, solo mostraría por un instante un horario que alguien
 * más acaba de tomar.
 */
class GetAvailableEventStartTimes
{
    /**
     * @return array<int, string> horas "H:i" disponibles para ese día y esa duración
     */
    public function handle(Business $business, EventSpace $space, EventSetting $settings, string $date, int $durationHours): array
    {
        $day = Carbon::parse($date, $settings->timezone)->startOfDay();

        if ($day->lt(now($settings->timezone)->startOfDay())) {
            return [];
        }

        if ($business->eventBlockedDates()->whereDate('date', $day->toDateString())->exists()) {
            return [];
        }

        $dayKey = strtolower($day->format('l'));
        $schedule = $settings->weekly_schedule[$dayKey] ?? null;

        if (! $schedule || $schedule['closed'] || empty($schedule['open']) || empty($schedule['close'])) {
            return [];
        }

        $openAt = $day->copy()->setTimeFromTimeString($schedule['open']);
        $closeAt = $day->copy()->setTimeFromTimeString($schedule['close']);
        $earliestStart = now($settings->timezone)->addHours($settings->min_advance_hours);

        // `starts_at`/`ends_at` se guardan en el huso de la aplicación
        // (ver `CreateEventReservation`), así que un `whereDate()` que
        // compare contra la fecha LOCAL del negocio puede no coincidir
        // con la fecha en que ese instante quedó guardado (ej. 8 p. m.
        // en Bogotá cae en la madrugada siguiente en UTC) — se filtra
        // por rango de instantes, nunca por texto de fecha. El motor de
        // BD compara el valor tal como se serializa, sin reinterpretar
        // su offset — por eso `$dayStart`/`$dayEnd` se convierten
        // explícitamente al huso de la aplicación ANTES de usarlos en
        // una consulta (igual que `CreateEventReservation` hace al
        // guardar); las comparaciones en memoria de más abajo
        // (`$slotStart->lt(...)`) sí comparan el instante real sin
        // importar el huso mostrado, así que esas se dejan en huso local.
        $dayStart = $openAt->copy()->startOfDay()->setTimezone(config('app.timezone'));
        $dayEnd = $openAt->copy()->endOfDay()->setTimezone(config('app.timezone'));

        $existing = EventReservation::where('event_space_id', $space->id)
            ->where(function ($q) {
                $q->where('status', EventReservation::CONFIRMADA)
                    ->orWhere(function ($q2) {
                        $q2->where('status', EventReservation::PENDIENTE_PAGO)->where('expires_at', '>', now());
                    });
            })
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        $blockingEvents = PublicEvent::where('event_space_id', $space->id)
            ->where('blocks_space', true)
            ->where('status', PublicEvent::PUBLICADO)
            ->where('starts_at', '<', $dayEnd)
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        $cursor = $openAt->copy();

        while ($cursor->copy()->addHours($durationHours)->lte($closeAt)) {
            $slotStart = $cursor->copy();
            $slotEnd = $slotStart->copy()->addHours($durationHours);

            $available = $slotStart->gte($earliestStart)
                && ! $existing->contains(fn ($r) => $slotStart->lt($r->ends_at) && $slotEnd->gt($r->starts_at))
                && ! $blockingEvents->contains(fn (PublicEvent $e) => $slotStart->lt($e->effectiveEndsAt()) && $slotEnd->gt($e->starts_at));

            if ($available) {
                $slots[] = $slotStart->format('H:i');
            }

            $cursor->addHour();
        }

        return $slots;
    }
}
