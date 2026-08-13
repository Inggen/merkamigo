<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\ApplyBillingProductPurchase;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El chatbot con IA de la vitrina (y cualquier add-on futuro del mismo
 * tipo) se desbloquea por plan Emprendedor o por `BusinessEntitlement`
 * comprado en "Impulsa tu negocio" — nunca por defecto en el plan Gratis.
 */
class BusinessEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_on_the_free_plan_without_an_addon_cannot_use_the_ai_chatbot(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Gratis'])->business;

        $this->assertFalse($business->canUseAiChatbot());
    }

    public function test_an_active_emprendedor_subscription_unlocks_the_ai_chatbot(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Emprendedor'])->business;

        $plan = Plan::create([
            'slug' => Plan::EMPRENDEDOR,
            'name' => 'Emprendedor',
            'billing_period' => Plan::MENSUAL,
            'is_active' => true,
            'position' => 1,
        ]);
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::ACTIVA,
        ]);

        $this->assertTrue($business->fresh()->canUseAiChatbot());
    }

    public function test_buying_the_ai_chatbot_addon_grants_a_permanent_entitlement(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Add-on'])->business;

        $product = BillingProduct::create([
            'slug' => 'asistente-ia',
            'name' => 'Asistente IA para tu vitrina',
            'price_cents' => 4990000,
            'kind' => BillingProduct::ENTITLEMENT,
            'payload' => ['entitlement_key' => BusinessEntitlement::AI_CHATBOT, 'expires_in_days' => null],
            'is_active' => true,
        ]);
        $payment = Payment::create([
            'business_id' => $business->id,
            'billing_product_id' => $product->id,
            'reference' => 'MKA-TEST-ENTITLEMENT',
            'amount_cents' => 4990000,
            'currency' => 'COP',
            'status' => Payment::APROBADO,
        ]);

        $this->assertFalse($business->canUseAiChatbot());

        app(ApplyBillingProductPurchase::class)->handle($payment);

        $this->assertTrue($business->fresh()->canUseAiChatbot());

        $entitlement = BusinessEntitlement::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(BusinessEntitlement::AI_CHATBOT, $entitlement->key);
        $this->assertNull($entitlement->expires_at);
    }

    public function test_an_expired_entitlement_does_not_unlock_the_ai_chatbot(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Vencido'])->business;

        BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($business->fresh()->canUseAiChatbot());
    }
}
