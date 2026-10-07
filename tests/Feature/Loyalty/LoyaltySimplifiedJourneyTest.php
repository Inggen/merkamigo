<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\DeliverLoyaltyRedemption;
use App\Domain\Loyalty\Actions\IssueLoyaltyIdentityToken;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Notifications\PointsAccrued;
use App\Domain\Loyalty\Notifications\RedemptionDelivered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F5.3 — "Prueba de recorrido": el objetivo final
 * completo en una sola prueba de integración, a través de rutas HTTP y
 * componentes Livewire reales (no solo llamadas directas a Actions),
 * exactamente en el orden que describe el documento: visitante descubre
 * sin login → registro vuelve al premio → compra da puntos una sola vez
 * → canje por CTA único → negocio confirma entrega → aviso y saldo
 * correctos.
 */
class LoyaltySimplifiedJourneyTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_the_full_simplified_journey_end_to_end(): void
    {
        Notification::fake();

        [$business, $owner] = $this->enrolledBusiness(); // 1 pt. por cada $1.000 COP (unitCents = 100.000)
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa',
            'points_cost' => 10,
            'full_cost_cents' => 800_000,
            'stock_total' => 5,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        // 1) Visitante descubre sin login: catálogo y detalle públicos.
        $this->get(route('premia.index'))
            ->assertOk()
            ->assertSee($reward->title);

        $this->get(route('premia.show', $reward))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee('Inicia sesión para canjear');

        // 2) El registro conserva el destino: un invitado sin cuenta que
        // pulsa "Canjear" cae al login con la acción exacta como
        // `intended`, y una vez autenticado, vuelve a ella.
        $redeemUrl = route('premia.redeem', $reward);
        $this->get($redeemUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $redeemUrl);

        $customer = User::factory()->create(['experience' => 'cliente']);

        // Sin puntos todavía: la misma acción ahora fallaría por saldo
        // insuficiente — confirma que "no acredita regalo automáticamente"
        // con solo registrarse.
        $this->actingAs($customer)->get($redeemUrl)->assertRedirect(route('premia.show', $reward));
        $this->assertSame(0, LoyaltyRedemption::count());

        // 3) Compra presencial: el negocio la registra desde su escáner.
        // Doble clic / reintento con la misma clave de idempotencia no
        // debe acreditar dos veces.
        $idempotencyKey = (string) Str::uuid();
        $amountCents = 1_000_000; // $10.000 COP → 10 Merkapuntos con esta política.

        app(RegisterLoyaltyPurchase::class)->handle(
            $business, $owner, $customer, $amountCents, $idempotencyKey,
        );
        app(RegisterLoyaltyPurchase::class)->handle(
            $business, $owner, $customer, $amountCents, $idempotencyKey,
        );

        $this->assertSame(1, LoyaltyPurchase::count());
        $this->assertSame(10, LoyaltyPurchase::first()->points_awarded);
        Notification::assertSentTo($customer, PointsAccrued::class);

        // 4) Canje con un solo CTA, ahora que sí hay saldo: la misma URL
        // de antes ya alcanza para reservar y manda a la sección del
        // cliente, donde el código queda listo para presentar.
        $this->actingAs($customer)->get($redeemUrl)->assertRedirect(route('merkapuntos'));

        $redemption = LoyaltyRedemption::firstOrFail();
        $this->assertSame(LoyaltyRedemption::RESERVADO, $redemption->status);
        $this->assertSame($customer->id, $redemption->account->user_id);

        $this->actingAs($customer)->get(route('merkapuntos'))
            ->assertOk()
            ->assertSee('Canje pendiente');

        // 5) El negocio confirma la entrega desde su escáner, con el
        // mismo token que el cliente tiene reservado.
        $rawToken = $redemption->token_encrypted;

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('scanInput', $rawToken)
            ->call('scan')
            ->assertSet('scanResult.type', 'delivery')
            ->call('confirmDelivery');

        // 6) Aviso y saldo correctos: el cliente recibe el aviso de
        // entrega y su saldo disponible queda en cero (gastó los 10
        // puntos que tenía, sin quedar deuda ni sobrante).
        $this->assertSame(LoyaltyRedemption::ENTREGADO, $redemption->fresh()->status);
        Notification::assertSentTo($customer, RedemptionDelivered::class);

        $account = $customer->loyaltyAccounts()->where('business_id', $business->id)->first();
        $this->assertSame(0, $account->availablePoints());

        $reward->refresh();
        $this->assertSame(1, $reward->stock_delivered);
        $this->assertSame(0, $reward->stock_reserved);
    }

    /**
     * F5.3, aceptación: "no usar el QR de cliente como canje."
     */
    public function test_the_customer_identity_qr_can_never_be_used_to_deliver_a_reward(): void
    {
        [$business] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $identityToken = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $this->expectException(LoyaltyActionException::class);

        app(DeliverLoyaltyRedemption::class)->findByToken($business, $identityToken);
    }
}
