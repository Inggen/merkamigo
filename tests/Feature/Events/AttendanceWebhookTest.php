<?php

namespace Tests\Feature\Events;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Actions\CreateEventAttendance;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * El webhook de Wompi de un negocio (`webhooks.wompi.negocio`) es uno
 * solo — lo comparten Marketplace (Order), reservas de ESPACIO
 * (EventPaymentAttempt) y reservas de CUPO a eventos públicos
 * (EventAttendancePaymentAttempt), ver `BusinessWompiWebhookController`.
 * Mismo set de pruebas que `EventReservationWebhookTest`, para la rama
 * nueva.
 */
class AttendanceWebhookTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    private function paidAttendance(Business $business, User $owner): EventAttendance
    {
        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Taller de pago',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 10,
            'price_cents' => 1000000,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        return app(CreateEventAttendance::class)->handle(
            $event->fresh(), 1, 'Ana', 'ana@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_a_validly_signed_webhook_confirms_the_matching_event_attendance(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();
        $attendance = $this->paidAttendance($business, $owner);
        $attempt = $attendance->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        $transactionId = 'wompi-evt-attendance-webhook';
        $checksum = hash('sha256', $transactionId.'APPROVED'.$timestamp.$business->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => $transactionId, 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();

        $this->assertSame(EventAttendance::CONFIRMADA, $attendance->fresh()->status);
        $this->assertSame(EventAttendancePaymentAttempt::APROBADO, $attempt->fresh()->status);
    }

    public function test_a_repeated_webhook_does_not_duplicate_the_confirmation(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();
        $attendance = $this->paidAttendance($business, $owner);
        $attempt = $attendance->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        $checksum = hash('sha256', 'txn-attendance-repeat'.'APPROVED'.$timestamp.$business->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn-attendance-repeat', 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();
        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();

        $this->assertSame(EventAttendance::CONFIRMADA, $attendance->fresh()->status);
        $this->assertSame(1, EventAttendance::where('business_id', $business->id)->count());
    }

    public function test_a_webhook_cannot_be_forged_with_another_businesss_secret(): void
    {
        [$businessA] = $this->eventsReadyBusiness();
        [$businessB, $ownerB] = $this->eventsReadyBusiness();
        $attendance = $this->paidAttendance($businessB, $ownerB);
        $attempt = $attendance->paymentAttempts()->first();

        $timestamp = now()->timestamp;
        // Firmado con el secreto del negocio A, enviado al webhook del negocio B.
        $checksum = hash('sha256', 'txn-attendance-forged'.'APPROVED'.$timestamp.$businessA->wompiCredential->events_secret);

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn-attendance-forged', 'status' => 'APPROVED', 'reference' => $attempt->reference]],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $businessB), $event)->assertStatus(401);
        $this->assertSame(EventAttendance::PENDIENTE_PAGO, $attendance->fresh()->status);
    }
}
