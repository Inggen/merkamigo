<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\ChargeEntitlementRenewal;
use App\Domain\Billing\Actions\ProcessEntitlementRenewals;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PR4 de TODO_VENTAS_RENTABILIDAD.md (decisión del usuario 2026-10-09):
 * el asistente IA pasa de pago único "de por vida" a suscripción
 * mensual con cobro automático, reutilizando la tarjeta ya guardada
 * para la renovación del plan.
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

    private function businessWithSavedCard(string $name): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => $name])->business;
        $business->update(['wompi_payment_source_id' => '123', 'auto_renew_enabled' => true]);

        return $business->fresh();
    }

    public function test_charging_an_entitlement_renewal_extends_its_expiration(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithSavedCard('Negocio Renovación');
        $product = $this->aiAssistantProduct();
        $entitlement = BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        $payment = app(ChargeEntitlementRenewal::class)->handle($business, $product);

        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame($product->id, $payment->billing_product_id);
        // Se extiende desde `now()` porque ya estaba vencido (perdió
        // esos días de atraso) — igual que documenta
        // `ApplyBillingProductPurchase::applyEntitlement()`.
        $this->assertTrue($entitlement->fresh()->expires_at->isAfter(now()->addDays(29)));
    }

    public function test_process_entitlement_renewals_charges_a_due_entitlement(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-2', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithSavedCard('Negocio Lote');
        $product = $this->aiAssistantProduct();
        BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subHour(),
        ]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(1, $count);
        $this->assertTrue($business->canUseAiChatbot());
    }

    public function test_process_entitlement_renewals_never_touches_a_lifetime_entitlement(): void
    {
        $business = $this->businessWithSavedCard('Negocio De Por Vida');
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
    }

    public function test_process_entitlement_renewals_skips_a_business_already_covered_by_its_top_plan(): void
    {
        $business = $this->businessWithSavedCard('Negocio Ya Con Plan');
        $product = $this->aiAssistantProduct();
        $plan = Plan::create([
            'slug' => 'negocios', 'name' => 'Negocios', 'price_cents' => 9900000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 2,
        ]);
        Subscription::create(['business_id' => $business->id, 'plan_id' => $plan->id, 'status' => Subscription::ACTIVA]);
        $entitlement = BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(0, $count);
        // No se cobró nada, pero el acceso sigue vivo por el plan.
        $this->assertTrue($business->canUseAiChatbot());
        $this->assertTrue($entitlement->fresh()->expires_at->isPast());
    }

    public function test_process_entitlement_renewals_skips_a_business_without_a_saved_card(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Tarjeta Lote'])->business;
        $product = $this->aiAssistantProduct();
        BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(0, $count);
        $this->assertSame(0, Payment::count());
    }

    public function test_process_entitlement_renewals_does_not_count_a_declined_charge(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-3', 'status' => 'DECLINED']], 200)]);

        $business = $this->businessWithSavedCard('Negocio Rechazado');
        $product = $this->aiAssistantProduct();
        $entitlement = BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        $count = app(ProcessEntitlementRenewals::class)->handle();

        $this->assertSame(0, $count);
        $this->assertFalse($business->fresh()->canUseAiChatbot());
        $this->assertTrue($entitlement->fresh()->expires_at->isPast());
    }

    public function test_the_artisan_command_runs_the_renewal(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-ent-4', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithSavedCard('Negocio Comando');
        $product = $this->aiAssistantProduct();
        BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $product->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('billing:process-entitlement-renewals')
            ->expectsOutput('Add-ons renovados: 1.')
            ->assertSuccessful();
    }
}
