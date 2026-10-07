<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CheckInEventAttendance;
use App\Domain\Events\Actions\CreateEventAttendance;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * `CheckInEventAttendance` — pedido del usuario (2026-10-08): "en la
 * zona de emprendedores debe poderse leer ese qr para verificar la
 * asistencia." Mismo criterio de seguridad que
 * `DeliverLoyaltyRedemption::findByToken()`: un código inválido o de
 * otro negocio nunca debe revelar datos de la reserva.
 */
class CheckInEventAttendanceTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    private function freeAttendance(): EventAttendance
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Taller gratuito',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 10,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        return app(CreateEventAttendance::class)->handle(
            $event->fresh(), 1, 'Lucía', 'lucia@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_valid_token_is_found_and_checked_in_by_the_owning_business(): void
    {
        $attendance = $this->freeAttendance();
        $owner = $attendance->business->members->first();
        $rawToken = $attendance->checkin_token_encrypted;

        $found = app(CheckInEventAttendance::class)->findByToken($attendance->business, $rawToken);
        $this->assertSame($attendance->id, $found->id);

        $checkedIn = app(CheckInEventAttendance::class)->handle($attendance->business, $owner, $found);
        $this->assertTrue($checkedIn->isCheckedIn());
        $this->assertSame($owner->id, $checkedIn->checked_in_by_user_id);
    }

    public function test_checking_in_the_same_token_twice_is_idempotent(): void
    {
        $attendance = $this->freeAttendance();
        $owner = $attendance->business->members->first();

        $first = app(CheckInEventAttendance::class)->handle($attendance->business, $owner, $attendance);
        $firstCheckinTime = $first->checked_in_at;

        $second = app(CheckInEventAttendance::class)->handle($attendance->business, $owner, $attendance->fresh());

        $this->assertTrue($firstCheckinTime->equalTo($second->checked_in_at));
    }

    public function test_a_token_cannot_be_used_by_a_different_business(): void
    {
        $attendance = $this->freeAttendance();
        [$otherBusiness] = $this->eventsReadyBusiness();
        $rawToken = $attendance->checkin_token_encrypted;

        $this->expectException(EventActionException::class);
        app(CheckInEventAttendance::class)->findByToken($otherBusiness, $rawToken);
    }

    public function test_a_malformed_token_is_rejected(): void
    {
        $attendance = $this->freeAttendance();

        $this->expectException(EventActionException::class);
        app(CheckInEventAttendance::class)->findByToken($attendance->business, 'not-a-real-token');
    }

    public function test_a_reservation_that_is_not_confirmed_cannot_be_checked_in(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Taller de pago',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 10,
            'price_cents' => 1000000,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        $pending = app(CreateEventAttendance::class)->handle(
            $event->fresh(), 1, 'Pedro', 'pedro@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $this->assertSame(EventAttendance::PENDIENTE_PAGO, $pending->status);

        $this->expectException(EventActionException::class);
        app(CheckInEventAttendance::class)->handle($business, $owner, $pending);
    }
}
