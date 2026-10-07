<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Actions\GetAvailableEventStartTimes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 7: "Pruebas
 * unitarias de... fechas y zona horaria." `event_settings.timezone` es
 * un campo propio por negocio (no asume que todo Merkamigo opera en el
 * mismo huso) — estas pruebas usan un huso distinto al que usa el resto
 * del suite (`America/Bogota`, UTC-5) para confirmar que el horario de
 * atención se evalúa en EL HUSO DEL NEGOCIO, no en UTC ni en el huso del
 * servidor.
 */
class EventTimezoneTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_available_slots_respect_the_businesss_own_timezone_not_utc(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        // Madrid (UTC+1/+2) — deliberadamente distinto de Bogotá para que
        // un error que confunda el huso del negocio con UTC o con el del
        // servidor se note de inmediato.
        $business->eventSetting->update(['timezone' => 'Europe/Madrid']);

        $localNoon = Carbon::parse('tomorrow 12:00', 'Europe/Madrid');

        $slots = app(GetAvailableEventStartTimes::class)->handle(
            $business, $space, $business->eventSetting->fresh(), $localNoon->toDateString(), 1,
        );

        // El horario de prueba abre 00:00-23:59 todos los días (ver
        // `SetsUpEventsBusiness`), así que mediodía LOCAL debe estar
        // disponible — si el código evaluara en UTC, una fecha/hora
        // distinta (o ninguna) aparecería como disponible.
        $this->assertContains('12:00', $slots);
    }

    public function test_a_reservation_created_at_local_midday_is_stored_with_the_correct_absolute_instant(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $business->eventSetting->update(['timezone' => 'Europe/Madrid', 'min_advance_hours' => 0]);

        $localStart = Carbon::parse('+2 days 15:00', 'Europe/Madrid');

        $reservation = app(CreateEventReservation::class)->handle(
            $business->fresh(), $space, $localStart, 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        // Mismo instante absoluto, sin importar en qué huso se comparen
        // — confirma que no se perdió ni se desplazó al convertir.
        $this->assertTrue($reservation->starts_at->equalTo($localStart));
    }

    public function test_two_businesses_in_different_timezones_do_not_interfere_with_each_others_availability(): void
    {
        [$bogota] = $this->eventsReadyBusiness('horas', 8000000);
        $bogotaSpace = $this->eventsSpace($bogota);

        [$madrid] = $this->eventsReadyBusiness('horas', 8000000);
        $madridSpace = $this->eventsSpace($madrid);
        $madrid->eventSetting->update(['timezone' => 'Europe/Madrid']);

        $start = Carbon::parse('+2 days 10:00', 'America/Bogota');

        app(CreateEventReservation::class)->handle(
            $bogota->fresh(), $bogotaSpace, $start, 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        // Mismo instante absoluto reservado en Bogotá no debe bloquear a
        // Madrid — son espacios de negocios distintos, el huso de cada
        // uno es solo para interpretar SU PROPIO horario de atención.
        $slots = app(GetAvailableEventStartTimes::class)->handle(
            $madrid->fresh(), $madridSpace, $madrid->fresh()->eventSetting, $start->clone()->setTimezone('Europe/Madrid')->toDateString(), 1,
        );

        $this->assertNotEmpty($slots);
    }

    public function test_an_evening_slot_in_bogota_is_still_found_under_the_correct_local_calendar_day(): void
    {
        // `config('app.timezone')` es UTC y el huso por defecto de un
        // negocio es `America/Bogota` (UTC-5, el default real de
        // `event_settings.timezone`) — una reserva de las 8 p. m.
        // hora de Bogotá cae en la madrugada del día siguiente en UTC.
        // Si el filtro por fecha de `GetAvailableEventStartTimes` no
        // respeta el huso del negocio, esa franja "desaparecería" del
        // día que el dueño y el cliente ven en su calendario.
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $business->eventSetting->update(['timezone' => 'America/Bogota']);

        $localEvening = Carbon::parse('+2 days 20:00', 'America/Bogota');

        $slots = app(GetAvailableEventStartTimes::class)->handle(
            $business->fresh(), $space, $business->fresh()->eventSetting, $localEvening->toDateString(), 1,
        );

        $this->assertContains('20:00', $slots);
    }

    public function test_an_evening_reservation_in_bogota_blocks_the_same_local_evening_slot_for_a_second_prospect(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $business->eventSetting->update(['timezone' => 'America/Bogota', 'min_advance_hours' => 0]);

        $localEvening = Carbon::parse('+2 days 20:00', 'America/Bogota');

        app(CreateEventReservation::class)->handle(
            $business->fresh(), $space, $localEvening->clone(), 1, 2, [], [],
            'Primero', 'primero@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $slots = app(GetAvailableEventStartTimes::class)->handle(
            $business->fresh(), $space, $business->fresh()->eventSetting, $localEvening->toDateString(), 1,
        );

        $this->assertNotContains('20:00', $slots);
    }
}
