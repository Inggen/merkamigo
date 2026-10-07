<?php

namespace Tests\Feature\Events;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 7 — prueba end-to-end
 * literal del documento: "configurar Kebero → reservar para seis →
 * pagar → confirmar → ver en panel." A diferencia de los demás tests de
 * este dominio, este arma el negocio "a mano" (sin `SetsUpEventsBusiness`)
 * precisamente para recorrer el panel de Fase 2 como lo haría el dueño
 * real, en vez de partir de un negocio ya configurado.
 */
class ReservationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuring_kebero_then_reserving_for_six_paying_and_confirming_shows_up_in_the_panel(): void
    {
        Notification::fake();

        $municipality = Municipality::firstOrCreate(['slug' => 'zipaquira'], ['name' => 'Zipaquirá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Kebero Música y Café',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);
        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);
        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_kebero',
            'private_key' => 'prv_test_kebero',
            'integrity_secret' => 'integrity-kebero',
            'events_secret' => 'events-secret-kebero',
            'environment' => 'sandbox',
        ], $owner);

        // 1) Configurar Kebero desde el panel (Fase 2): horario abierto
        // todos los días, modalidad híbrida, espacio, menú y equipos.
        $openAllWeek = [];
        foreach (Business::DAY_LABELS as $day => $label) {
            $openAllWeek[$day] = ['closed' => false, 'open' => '00:00', 'close' => '23:59'];
        }

        $panel = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('enabled', true)
            ->set('publicEventsEnabled', true)
            ->set('privateReservationsEnabled', true)
            ->set('maxDurationHours', 3)
            ->set('minAdvanceHours', 0)
            ->set('weeklySchedule', $openAllWeek)
            ->call('saveGeneral')
            ->assertHasNoErrors()
            ->set('pricingMode', 'hibrido')
            ->set('hourlyRateCop', 80000)
            ->call('saveRates')
            ->assertHasNoErrors()
            ->set('spaceName', 'Salón principal')
            ->call('addSpace')
            ->assertHasNoErrors();

        $panel->set('dishName', 'Crepes')->set('dishPriceCop', 18000)->call('addDish')->assertHasNoErrors();
        $panel->set('dishName', 'Sándwiches')->set('dishPriceCop', 16000)->call('addDish')->assertHasNoErrors();
        $panel->set('dishName', 'Estroganoff')->set('dishPriceCop', 22000)->call('addDish')->assertHasNoErrors();

        $this->assertTrue($business->fresh()->eventSetting->enabled);
        $this->assertSame(3, $business->fresh()->eventDishes()->count());

        // 2) Un prospecto cotiza y reserva para seis personas (ejemplo
        // del propio documento: 2 crepes + 3 sándwiches + 1 estroganoff).
        $dishes = $business->eventDishes()->get()->keyBy('name');

        $wizard = Livewire::test('pages::eventos.reservar', ['business' => $business->fresh()])
            ->set('durationHours', 2);

        $startTime = $wizard->get('availableStartTimes')[0];

        $wizard
            ->set('startTime', $startTime)
            ->set('partySize', 6)
            ->call('incrementDish', $dishes['Crepes']->id)
            ->call('incrementDish', $dishes['Crepes']->id)
            ->call('incrementDish', $dishes['Sándwiches']->id)
            ->call('incrementDish', $dishes['Sándwiches']->id)
            ->call('incrementDish', $dishes['Sándwiches']->id)
            ->call('incrementDish', $dishes['Estroganoff']->id)
            ->set('prospectName', 'Juan Pérez')
            ->set('prospectEmail', 'juan@example.com')
            ->set('prospectPhone', '+573001112233')
            ->set('termsAccepted', true)
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = EventReservation::where('business_id', $business->id)->firstOrFail();

        // Lugar (2h × $80.000) + platos (2×18.000 + 3×16.000 + 1×22.000) = 160.000 + 106.000 = 266.000 COP.
        $this->assertSame(26600000, $reservation->total_cents);
        $this->assertSame(EventReservation::PENDIENTE_PAGO, $reservation->status);
        $this->assertSame(6, $reservation->party_size);

        // 3) Pagar: el checkout entrega el widget de Wompi firmado con la
        // llave DEL NEGOCIO.
        $checkoutResponse = $this->getJson(route('eventos.reservas.checkout', $reservation));
        $checkoutResponse->assertOk();
        $this->assertSame('pub_test_kebero', $checkoutResponse->json('publicKey'));
        $reference = $checkoutResponse->json('reference');

        // 4) Confirmar: Wompi aprueba el pago (vía retorno del checkout,
        // el mismo camino que recorrería el navegador del prospecto).
        Http::fake(['*/transactions/*' => Http::response([
            'data' => ['id' => 'wompi-e2e-txn', 'status' => 'APPROVED', 'reference' => $reference],
        ], 200)]);

        $attempt = $reservation->paymentAttempts()->where('reference', $reference)->firstOrFail();
        $this->get(route('eventos.reservas.retorno', $attempt).'?id=wompi-e2e-txn')->assertOk();

        $this->assertSame(EventReservation::CONFIRMADA, $reservation->fresh()->status);

        // 5) Ver en el panel: la reserva confirmada aparece en la
        // pestaña Reservas con sus datos reales.
        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('activeTab', 'reservas')
            ->assertSee('Juan Pérez')
            ->assertSee('6 personas');
    }
}
