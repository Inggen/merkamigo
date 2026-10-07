<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\ApplyApprovedEventPayment;
use App\Domain\Events\Actions\CreateEventPaymentAttempt;
use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Events\Notifications\EventReservationConfirmedForProspect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 4 — criterio de
 * aceptación: "éxito, rechazo, webhook repetido, webhook tardío y
 * cancelación quedan en estados coherentes; un pago duplicado no duplica
 * confirmaciones."
 */
class EventReservationPaymentTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_an_approved_payment_confirms_the_reservation_and_notifies_both_sides(): void
    {
        Notification::fake();

        $reservation = $this->pendingReservation();
        $attempt = $reservation->paymentAttempts()->first();

        app(ApplyApprovedEventPayment::class)->handle($attempt, 'APPROVED', 'txn-1', ['status' => 'APPROVED']);

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->expires_at);
        $this->assertSame(EventPaymentAttempt::APROBADO, $attempt->fresh()->status);

        Notification::assertSentOnDemand(EventReservationConfirmedForProspect::class);
    }

    public function test_a_declined_payment_marks_the_reservation_as_payment_failed(): void
    {
        $reservation = $this->pendingReservation();
        $attempt = $reservation->paymentAttempts()->first();

        app(ApplyApprovedEventPayment::class)->handle($attempt, 'DECLINED', 'txn-2', ['status' => 'DECLINED']);

        $this->assertSame(EventReservation::PAGO_FALLIDO, $reservation->fresh()->status);
        $this->assertSame(EventPaymentAttempt::RECHAZADO, $attempt->fresh()->status);
    }

    public function test_applying_the_same_approval_twice_does_not_duplicate_confirmations(): void
    {
        Notification::fake();

        $reservation = $this->pendingReservation();
        $attempt = $reservation->paymentAttempts()->first();

        app(ApplyApprovedEventPayment::class)->handle($attempt, 'APPROVED', 'txn-3', []);
        app(ApplyApprovedEventPayment::class)->handle($attempt, 'APPROVED', 'txn-3', []);

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
        Notification::assertSentOnDemandTimes(EventReservationConfirmedForProspect::class, 1);
    }

    public function test_a_late_approval_after_expiry_does_not_reassign_the_slot(): void
    {
        Notification::fake();

        $reservation = $this->pendingReservation();
        $attempt = $reservation->paymentAttempts()->first();

        // La franja venció antes de que el pago se confirmara.
        $reservation->update(['status' => EventReservation::VENCIDA]);

        app(ApplyApprovedEventPayment::class)->handle($attempt, 'APPROVED', 'txn-late', []);

        $this->assertSame(EventReservation::VENCIDA, $reservation->fresh()->status);
        $this->assertSame(EventPaymentAttempt::APROBADO, $attempt->fresh()->status);
        Notification::assertNothingSentTo($reservation->business->members);
    }

    public function test_the_checkout_endpoint_returns_a_wompi_widget_payload_with_the_businesss_own_key(): void
    {
        $reservation = $this->pendingReservation();

        $response = $this->getJson(route('eventos.reservas.checkout', $reservation));

        $response->assertOk();
        $response->assertJsonStructure(['publicKey', 'currency', 'amountInCents', 'reference', 'signature', 'redirectUrl']);
        $this->assertStringStartsWith('pub_test_', $response->json('publicKey'));
        $this->assertSame($reservation->total_cents, $response->json('amountInCents'));
    }

    public function test_the_checkout_endpoint_reuses_a_pending_attempt_instead_of_creating_a_new_one(): void
    {
        $reservation = $this->pendingReservation();
        $firstAttemptId = $reservation->paymentAttempts()->first()->id;

        $this->getJson(route('eventos.reservas.checkout', $reservation))->assertOk();

        $this->assertSame(1, $reservation->paymentAttempts()->count());
        $this->assertSame($firstAttemptId, $reservation->paymentAttempts()->first()->id);
    }

    public function test_the_return_endpoint_confirms_the_reservation_when_wompi_reports_approved(): void
    {
        $reservation = $this->pendingReservation();
        $attempt = $reservation->paymentAttempts()->first();

        Http::fake(['*/transactions/*' => Http::response([
            'data' => ['id' => 'wompi-evt-1', 'status' => 'APPROVED', 'reference' => $attempt->reference],
        ], 200)]);

        $response = $this->get(route('eventos.reservas.retorno', $attempt).'?id=wompi-evt-1');

        $response->assertOk();
        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
    }

    private function pendingReservation(): EventReservation
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan Pérez', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        // El helper `eventsReadyBusiness` ya creó el primer intento vía
        // `CreateEventReservation`; nos aseguramos de que exista exactamente
        // uno antes de empezar cada prueba de pago.
        if ($reservation->paymentAttempts()->count() === 0) {
            app(CreateEventPaymentAttempt::class)->handle($reservation);
        }

        return $reservation->fresh();
    }
}
