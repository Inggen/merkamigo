<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Models\EventReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * Cotizador público (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase
 * 3) — página completa en `m/{business:slug}/eventos/reservar`.
 */
class EventReservationWizardTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_it_is_not_reachable_when_the_business_has_not_enabled_private_reservations(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $business->eventSetting->update(['private_reservations_enabled' => false]);

        $this->get(route('vitrinas.eventos.reservar', $business))->assertNotFound();
    }

    public function test_a_guest_can_quote_and_create_a_reservation_without_an_account(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $this->eventsSpace($business);

        $component = Livewire::test('pages::eventos.reservar', ['business' => $business]);

        $component->assertSet('durationHours', 1);
        $this->assertNotEmpty($component->get('availableStartTimes'));

        $component->set('durationHours', 2);
        $startTime = $component->get('availableStartTimes')[0];

        $component
            ->set('startTime', $startTime)
            ->set('partySize', 6)
            ->set('prospectName', 'Juan Pérez')
            ->set('prospectEmail', 'juan@example.com')
            ->set('prospectPhone', '+573001112233')
            ->set('termsAccepted', true)
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = EventReservation::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(16000000, $reservation->total_cents);
        $this->assertSame(6, $reservation->party_size);
        $this->assertNull($reservation->customer_user_id);
        $component->assertSet('createdReservationId', $reservation->id);
    }

    public function test_the_vitrina_shows_the_cta_only_when_private_reservations_are_enabled(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee(route('vitrinas.eventos.reservar', $business), false);

        $business->eventSetting->update(['private_reservations_enabled' => false]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertDontSee(route('vitrinas.eventos.reservar', $business), false);
    }

    public function test_a_duration_beyond_the_configured_maximum_is_rejected_server_side(): void
    {
        [$business] = $this->eventsReadyBusiness('horas', 8000000);
        $this->eventsSpace($business);

        $component = Livewire::test('pages::eventos.reservar', ['business' => $business])
            ->set('durationHours', 99)
            ->set('startTime', '10:00')
            ->set('partySize', 2)
            ->set('prospectName', 'Juan')
            ->set('prospectEmail', 'juan@example.com')
            ->set('prospectPhone', '+573001112233')
            ->set('termsAccepted', true)
            ->call('submit');

        $component->assertHasErrors('submit');
        $this->assertSame(0, EventReservation::where('business_id', $business->id)->count());
    }
}
