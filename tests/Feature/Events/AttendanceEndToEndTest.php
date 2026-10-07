<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Models\EventAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md — pedido del usuario
 * (2026-10-08): "la reserva del evento... se debe poder hacer la
 * reserva según el cupo y disponibilidad del evento... si es sin pago,
 * el usuario solo hace la reserva, se le genera un qr para la entrada
 * (en la zona de emprendedores debe poderse leer ese qr para verificar
 * la asistencia) y también deben haber unas métricas." Prueba
 * end-to-end literal de ese pedido, para un taller GRATUITO: reservar
 * cupo desde la ficha pública → recibir el QR de la entrada → el
 * negocio lo lee y confirma la asistencia en su panel → las métricas
 * del panel reflejan la reserva, el pago y la asistencia.
 */
class AttendanceEndToEndTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_reserving_a_free_workshop_generates_a_qr_ticket_that_the_business_can_check_in_and_count(): void
    {
        Notification::fake();

        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Taller de cerámica: crea y conecta',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 2,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        // 1) Un visitante reserva un cupo gratuito desde la ficha pública.
        $widget = Livewire::test('event-attendance-widget', ['event' => $event->fresh()])
            ->set('attendeeName', 'María López')
            ->set('attendeeEmail', 'maria@example.com')
            ->set('attendeePhone', '+573001112233')
            ->call('submit')
            ->assertHasNoErrors();

        $attendance = EventAttendance::where('public_event_id', $event->id)->firstOrFail();
        $this->assertSame(EventAttendance::CONFIRMADA, $attendance->status);
        $this->assertSame(0, $attendance->total_cents);
        $this->assertNotFalse($widget->get('ticketUrl'));

        // 2) El cupo gratuito queda reflejado de inmediato en la
        // disponibilidad del evento ("según el cupo y disponibilidad").
        $this->assertSame(1, $event->fresh()->spotsRemaining());

        // 3) "Mi entrada": solo alcanzable con el enlace firmado — nunca
        // adivinando el id — y muestra el QR.
        $ticketUrl = $widget->get('ticketUrl');
        $this->get($ticketUrl)->assertOk()->assertSee('María López');

        // Sin firma, la misma ruta no es alcanzable (protege datos del
        // asistente aunque alguien adivine el id secuencial).
        $this->get(route('eventos.entradas.show', ['eventAttendance' => $attendance->id]))->assertForbidden();

        $this->get(route('eventos.entradas.qr', ['eventAttendance' => $attendance->id]))->assertForbidden();

        // 4) En la puerta: el negocio lee el QR (el mismo código que
        // codifica la imagen) desde la pestaña Check-in de su panel.
        $rawToken = $attendance->checkin_token_encrypted;

        $panel = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('activeTab', 'checkin')
            ->set('checkinInput', $rawToken)
            ->call('lookupCheckin')
            ->assertHasNoErrors()
            ->assertSee('María López');

        $panel->call('confirmCheckin')->assertHasNoErrors();

        $this->assertTrue($attendance->fresh()->isCheckedIn());
        $this->assertSame($owner->id, $attendance->fresh()->checked_in_by_user_id);

        // Reescanear el mismo QR (doble lectura) es idempotente, no un error.
        $panel->set('checkinInput', $rawToken)->call('lookupCheckin')->assertHasNoErrors();
        $panel->call('confirmCheckin')->assertHasNoErrors();
        $this->assertSame($owner->id, $attendance->fresh()->checked_in_by_user_id);

        // 5) Métricas del panel: "cantidad de reservas, cantidad de
        // pagos realizados en línea, cantidad de personas que asistieron."
        $panel->assertSet('attendanceMetrics', [
            'reservations' => 1,
            'onlinePayments' => 0,
            'attended' => 1,
        ]);
    }

    public function test_reserving_a_paid_workshop_requires_payment_before_the_qr_ticket_exists(): void
    {
        Notification::fake();

        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Noche de música y café',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 10,
            'price_cents' => 2000000,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        $widget = Livewire::test('event-attendance-widget', ['event' => $event->fresh()])
            ->set('quantity', 2)
            ->set('attendeeName', 'Carlos Ruiz')
            ->set('attendeeEmail', 'carlos@example.com')
            ->set('attendeePhone', '+573001112233')
            ->call('submit')
            ->assertHasNoErrors();

        $attendance = EventAttendance::where('public_event_id', $event->id)->firstOrFail();
        $this->assertSame(EventAttendance::PENDIENTE_PAGO, $attendance->status);
        $this->assertSame(4000000, $attendance->total_cents);
        $this->assertNull($widget->get('ticketUrl'));

        // El cupo se retiene mientras el pago está pendiente (dos de
        // diez cupos ya no están disponibles para otro visitante).
        $this->assertSame(8, $event->fresh()->spotsRemaining());

        $checkoutResponse = $this->getJson(route('eventos.entradas.checkout', $attendance));
        $checkoutResponse->assertOk();
        $reference = $checkoutResponse->json('reference');

        Http::fake(['*/transactions/*' => Http::response([
            'data' => ['id' => 'wompi-attendance-e2e', 'status' => 'APPROVED', 'reference' => $reference],
        ], 200)]);

        $attempt = $attendance->paymentAttempts()->where('reference', $reference)->firstOrFail();
        $this->get(route('eventos.entradas.retorno', $attempt).'?id=wompi-attendance-e2e')->assertOk();

        $attendance->refresh();
        $this->assertSame(EventAttendance::CONFIRMADA, $attendance->status);

        // Ahora sí existe la entrada con QR.
        $ticketUrl = URL::signedRoute('eventos.entradas.show', ['eventAttendance' => $attendance->id]);
        $this->get($ticketUrl)->assertOk()->assertSee('Carlos Ruiz');

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->assertSet('attendanceMetrics', [
                'reservations' => 1,
                'onlinePayments' => 1,
                'attended' => 0,
            ]);
    }

    public function test_a_sold_out_event_rejects_a_new_reservation_that_exceeds_remaining_capacity(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Mercado artesanal de Zipaquirá',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity' => 1,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        Livewire::test('event-attendance-widget', ['event' => $event->fresh()])
            ->set('attendeeName', 'Primero')
            ->set('attendeeEmail', 'primero@example.com')
            ->set('attendeePhone', '+573001112233')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(0, $event->fresh()->spotsRemaining());

        Livewire::test('event-attendance-widget', ['event' => $event->fresh()])
            ->set('attendeeName', 'Segundo')
            ->set('attendeeEmail', 'segundo@example.com')
            ->set('attendeePhone', '+573001112233')
            ->call('submit')
            ->assertHasErrors('submit');

        $this->assertSame(1, EventAttendance::where('public_event_id', $event->id)->count());
    }

    /**
     * Pedido del usuario (2026-10-06): "revisa que todos los mensajes
     * de validación estén en español" — reporte real, con captura, de
     * este mismo formulario mostrando "The attendee phone field is
     * required." en vez de en español. Causa: `APP_LOCALE=es` pero el
     * proyecto nunca había publicado `lang/es/validation.php`, así que
     * Laravel caía al inglés por defecto del framework. Ver
     * `lang/es/validation.php`.
     */
    public function test_the_missing_phone_validation_error_is_in_spanish(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Taller sin teléfono',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        ], $owner);
        app(ManagePublicEvent::class)->publish($event, $owner);

        Livewire::test('event-attendance-widget', ['event' => $event->fresh()])
            ->set('attendeeName', 'María López')
            ->set('attendeeEmail', 'maria@example.com')
            ->set('attendeePhone', '')
            ->call('submit')
            ->assertHasErrors(['attendeePhone' => 'required'])
            ->assertSee('El campo teléfono es obligatorio.');
    }
}
