<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Models\EventReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

class ExpireEventReservationsCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_it_expires_pending_reservations_past_their_hold_and_leaves_others_untouched(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $expired = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay()->setTime(10, 0), 1, 2, [], [],
            'Vencido', 'vencido@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $expired->update(['expires_at' => now()->subMinute()]);

        $stillValid = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay()->setTime(14, 0), 1, 2, [], [],
            'Vigente', 'vigente@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $this->artisan('events:expire-reservations')->assertSuccessful();

        $this->assertSame(EventReservation::VENCIDA, $expired->fresh()->status);
        $this->assertSame(EventReservation::PENDIENTE_PAGO, $stillValid->fresh()->status);
    }

    public function test_it_does_not_expire_an_already_confirmed_reservation(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $reservation->update(['status' => EventReservation::CONFIRMADA, 'expires_at' => now()->subMinute()]);

        $this->artisan('events:expire-reservations')->assertSuccessful();

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
    }
}
