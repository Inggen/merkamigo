<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\ChargeEntitlementRenewal;
use App\Domain\Billing\Actions\ProcessEntitlementRenewals;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Notifications\EntitlementRenewalDue;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * PR4 de TODO_VENTAS_RENTABILIDAD.md (decisión del usuario 2026-10-09):
 * el asistente IA pasa de pago único "de por vida" a suscripción
 * mensual con cobro automático. Pedido del usuario 2026-10-10: "deja el
 * cobro mensual igual de robusto que la renovación de planes" — mismo
 * periodo de gracia, mismo criterio de reintento, misma notificación
 * cuando no hay tarjeta (ver `AutoDebitRenewalTest`, el equivalente de
 * planes que esta prueba espeja).
 */
class RecurringEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function aiAssistantProduct(): BillingProduct
    {
        return BillingProduct::create([
            'slug' => 'asistente-ia',
            'name' => 'Asistente IA para tu vitrina',
            'description' => 'Chat con IA.',
            'price_cents' => 4990000,
            'kind' => BillingProduct::ENTITLEMENT,
            'payload' => ['entitlement_key' => BusinessEntitlement::AI_CHATBOT, 'expires_in_days' => 30],
            'is_active' => true,
        ]);
    }

    private function businessWithExpiredEntitlement(BillingProduct $product, string $name, bool $withCard = false): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => $name])->business;

        if ($withCard) {
            $business->update(['wompi_payment_source_id' => '3891', 'auto_renew_enabled' => true]);
        }

        $entitlement = BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        return [$business->fresh(), $entitlement];
    }

    public function test_expired_active_entitlement_without_a_card_enters_grace_period_and_notifies(): void
    {
        Notification::fake();

        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Sin Tarjeta');

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(1, $count);
        $entitlement->refresh();
        $this->assertSame(BusinessEntitlement::EN_GRACIA, $entitlement->status);
        $this->assertNotNull($entitlement->grace_ends_at);
        // La gracia conserva el acceso — ese es justamente su propósito.
        $this->assertTrue($business->fresh()->canUseAiChatbot());
        $this->assertSame(0, Payment::count());

        Notification::assertSentTo($business->fresh()->members->first(), EntitlementRenewalDue::class);
    }

    public function test_expired_active_entitlement_with_a_saved_card_is_charged_automatically(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Con Tarjeta', withCard: true);

        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-1', 'status' => 'APPROVED']], 200)]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(1, $count);
        $entitlement->refresh();
        $this->assertSame(BusinessEntitlement::ACTIVA, $entitlement->status);
        $this->assertTrue($entitlement->expires_at->isFuture());
        $this->assertTrue($business->fresh()->canUseAiChatbot());

        $payment = Payment::where('business_id', $business->id)->latest()->first();
        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame($product->id, $payment->billing_product_id);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/transactions')
            && $request['payment_source_id'] === 3891);
    }

    public function test_renewal_charges_exactly_once_per_cycle(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Ciclo', withCard: true);

        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'wompi-ent-cycle-1', 'status' => 'APPROVED']], 200)
            ->push(['data' => ['id' => 'wompi-ent-cycle-2', 'status' => 'APPROVED']], 200),
        ]);

        $firstRunCount = app(ProcessEntitlementRenewals::class)->handle();
        $this->assertSame(1, $firstRunCount);
        $this->assertSame(1, Payment::where('business_id', $business->id)->count());

        // Repetir la misma corrida el mismo día no debe volver a cobrar —
        // `expires_at` ya quedó en el futuro tras el primer cobro.
        $secondRunCount = app(ProcessEntitlementRenewals::class)->handle();
        $this->assertSame(0, $secondRunCount);
        $this->assertSame(1, Payment::where('business_id', $business->id)->count());

        // Un mes después vence de nuevo — debe cobrar una vez más.
        $entitlement->fresh()->update(['expires_at' => now()->subDay()]);

        $thirdRunCount = app(ProcessEntitlementRenewals::class)->handle();
        $this->assertSame(1, $thirdRunCount);
        $this->assertSame(2, Payment::where('business_id', $business->id)->count());
    }

    public function test_declined_automatic_charge_enters_grace_period(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Rechazado', withCard: true);

        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-2', 'status' => 'DECLINED']], 200)]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(1, $count);
        $entitlement->refresh();
        $this->assertSame(BusinessEntitlement::EN_GRACIA, $entitlement->status);
        // En gracia, aunque el cobro falló, el acceso sigue vivo.
        $this->assertTrue($business->fresh()->canUseAiChatbot());

        $payment = Payment::where('business_id', $business->id)->latest()->first();
        $this->assertSame(Payment::RECHAZADO, $payment->status);
    }

    public function test_grace_period_retry_succeeds_and_restores_active_status(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Reintento', withCard: true);

        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'wompi-ent-3a', 'status' => 'DECLINED']])
            ->push(['data' => ['id' => 'wompi-ent-3b', 'status' => 'APPROVED']]),
        ]);

        app(ProcessEntitlementRenewals::class)->handle();
        $this->assertSame(BusinessEntitlement::EN_GRACIA, $entitlement->fresh()->status);

        $this->travel(1)->day();
        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(1, $count);
        $entitlement->refresh();
        $this->assertSame(BusinessEntitlement::ACTIVA, $entitlement->status);
        $this->assertNull($entitlement->grace_ends_at);
        $this->assertTrue($entitlement->expires_at->isFuture());
    }

    public function test_once_grace_period_ends_without_payment_access_is_lost_and_is_no_longer_retried(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Gracia Agotada');

        app(ProcessEntitlementRenewals::class)->handle();
        $entitlement->refresh();
        $this->assertTrue($business->fresh()->canUseAiChatbot());

        $this->travel(ProcessEntitlementRenewals::GRACE_DAYS + 1)->days();

        $this->assertFalse($business->fresh()->canUseAiChatbot());

        $count = app(ProcessEntitlementRenewals::class)->handle();
        $this->assertSame(0, $count);
        $this->assertSame(BusinessEntitlement::EN_GRACIA, $entitlement->fresh()->status);
    }

    public function test_process_entitlement_renewals_never_touches_a_lifetime_entitlement(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio De Por Vida'])->business;
        $product = $this->aiAssistantProduct();
        $entitlement = BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => null,
        ]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(0, $count);
        $this->assertNull($entitlement->fresh()->expires_at);
        $this->assertSame(BusinessEntitlement::ACTIVA, $entitlement->fresh()->status);
    }

    public function test_process_entitlement_renewals_skips_a_business_already_covered_by_its_top_plan(): void
    {
        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Ya Con Plan', withCard: true);
        $plan = Plan::create([
            'slug' => 'negocios', 'name' => 'Negocios', 'price_cents' => 9900000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 2,
        ]);
        Subscription::create(['business_id' => $business->id, 'plan_id' => $plan->id, 'status' => Subscription::ACTIVA]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(0, $count);
        $this->assertSame(0, Payment::count());
        // No se cobró nada, pero el acceso sigue vivo por el plan.
        $this->assertTrue($business->canUseAiChatbot());
        $this->assertTrue($entitlement->fresh()->expires_at->isPast());
    }

    public function test_the_artisan_command_runs_the_renewal(): void
    {
        $product = $this->aiAssistantProduct();
        $this->businessWithExpiredEntitlement($product, 'Negocio Comando', withCard: true);

        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-4', 'status' => 'APPROVED']], 200)]);

        $this->artisan('billing:process-entitlement-renewals')
            ->expectsOutput('Add-ons renovados: 1.')
            ->assertSuccessful();
    }

    public function test_charging_an_entitlement_renewal_directly_extends_its_expiration(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-5', 'status' => 'APPROVED']], 200)]);

        $product = $this->aiAssistantProduct();
        [$business, $entitlement] = $this->businessWithExpiredEntitlement($product, 'Negocio Cobro Directo', withCard: true);

        $payment = app(ChargeEntitlementRenewal::class)->handle($business, $product);

        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame($product->id, $payment->billing_product_id);
        $this->assertTrue($entitlement->fresh()->expires_at->isFuture());
    }
}
