<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3 — criterio de
 * aceptación: "duración superior a tres horas es rechazada; dos
 * prospectos no pueden confirmar el último cupo simultáneamente."
 */
class CreateEventReservationTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_it_creates_a_reservation_with_a_server_calculated_total_and_a_payment_attempt(): void
    {
        [$business] = $this->eventsReadyBusiness('hibrido', 8000000);
        $space = $this->eventsSpace($business);
        $crepes = $this->eventsDish($business, 'Crepes', 2500000);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 2, 6,
            [['dish_id' => $crepes->id, 'quantity' => 2]],
            [],
            'Juan Pérez', 'juan@example.com', '+573001112233',
            null, (string) Str::uuid(),
        );

        $this->assertSame(EventReservation::PENDIENTE_PAGO, $reservation->status);
        $this->assertSame(16000000, $reservation->space_total_cents);
        $this->assertSame(5000000, $reservation->dishes_total_cents);
        $this->assertSame(21000000, $reservation->total_cents);
        $this->assertNotNull($reservation->expires_at);
        $this->assertSame(1, $reservation->dishes()->count());
        $this->assertSame(1, EventPaymentAttempt::where('event_reservation_id', $reservation->id)->count());
    }

    public function test_a_duration_longer_than_the_configured_maximum_is_rejected(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 4, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_reservation_that_exceeds_space_capacity_is_rejected(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business, capacity: 10);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 11, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_blocked_date_is_rejected(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $date = now()->addDay();
        $business->eventBlockedDates()->create(['date' => $date->toDateString()]);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business, $space, $date->copy()->setTime(14, 0), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_equipment_beyond_declared_availability_is_rejected(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $sonido = $this->eventsGlobalEquipment($business, 'sonido-cap-test');
        $sonido->update(['quantity_available' => 1]);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [],
            [['equipment_id' => $sonido->id, 'quantity' => 2]],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_reservation_cannot_be_created_without_private_reservations_enabled(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $business->eventSetting->update(['private_reservations_enabled' => false]);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business->fresh(), $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_repeating_the_same_idempotency_key_returns_the_same_reservation(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $key = (string) Str::uuid();

        $first = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, $key,
        );

        $second = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, $key,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, EventReservation::count());
    }

    public function test_two_prospects_cannot_both_reserve_the_same_overlapping_slot(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $start = now()->addDay()->setTime(14, 0);

        app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy(), 2, 2, [], [],
            'Primero', 'primero@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $this->expectException(EventActionException::class);

        // Mismo espacio, horario que se solapa (empieza una hora después,
        // dentro de la franja de 2 horas ya tomada).
        app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy()->addHour(), 2, 2, [], [],
            'Segundo', 'segundo@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_non_overlapping_slot_on_the_same_day_can_still_be_reserved(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $start = now()->addDay()->setTime(10, 0);

        app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy(), 2, 2, [], [],
            'Primero', 'primero@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        // Empieza justo cuando termina la primera reserva — no se solapa.
        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy()->addHours(2), 2, 2, [], [],
            'Segundo', 'segundo@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $this->assertSame(2, EventReservation::count());
        $this->assertNotNull($reservation->id);
    }

    public function test_an_expired_pending_reservation_no_longer_blocks_the_slot(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $start = now()->addDay()->setTime(14, 0);

        $first = app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy(), 2, 2, [], [],
            'Primero', 'primero@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $first->update(['expires_at' => now()->subMinute()]);

        $second = app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy(), 2, 2, [], [],
            'Segundo', 'segundo@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $this->assertNotNull($second->id);
        $this->assertNotSame($first->id, $second->id);
    }
}
