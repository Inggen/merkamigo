<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * El webhook de Wompi de un negocio (`webhooks.wompi.negocio`) es uno
 * solo — lo comparten Marketplace (Order) y reservas de eventos
 * (EventPaymentAttempt), ver `BusinessWompiWebhookController`.
 */
class EventReservationWebhookTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_a_validly_signed_webhook_confirms_the_matching_event_reservation(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $attempt = $reservation->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        $transactionId = 'wompi-evt-webhook';
        $checksum = hash('sha256', $transactionId.'APPROVED'.$timestamp.$business->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => $transactionId, 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
        $this->assertSame(EventPaymentAttempt::APROBADO, $attempt->fresh()->status);
    }

    public function test_a_repeated_webhook_does_not_duplicate_the_confirmation(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $attempt = $reservation->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        $checksum = hash('sha256', 'txn-repeat'.'APPROVED'.$timestamp.$business->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn-repeat', 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();
        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);
        $this->assertSame(1, EventReservation::where('business_id', $business->id)->count());
    }

    public function test_a_webhook_cannot_be_forged_with_another_businesss_secret(): void
    {
        [$businessA] = $this->eventsReadyBusiness('horas', 8000000);
        [$businessB] = $this->eventsReadyBusiness('horas', 8000000);
        $spaceB = $this->eventsSpace($businessB);

        $reservation = app(CreateEventReservation::class)->handle(
            $businessB, $spaceB, now()->addDay(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
        $attempt = $reservation->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        // Firmado con el secreto del negocio A, enviado al webhook del negocio B.
        $checksum = hash('sha256', 'txn-forged'.'APPROVED'.$timestamp.$businessA->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn-forged', 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $businessB), $event)->assertStatus(401);
        $this->assertSame(EventReservation::PENDIENTE_PAGO, $reservation->fresh()->status);
    }
}
