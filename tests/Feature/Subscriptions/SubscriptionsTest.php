<?php

namespace Tests\Feature\Subscriptions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Actions\CancelCustomerSubscription;
use App\Domain\Subscriptions\Actions\CreateSubscriptionPlan;
use App\Domain\Subscriptions\Actions\RenewCustomerSubscriptions;
use App\Domain\Subscriptions\Actions\SubscribeToProduct;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Domain\Subscriptions\Models\Entitlement;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Fase 8/9 del TODO social (Sprint 6): suscripciones cliente → negocio y
 * productos digitales — el cobro periódico va directo a la cuenta Wompi
 * del negocio, reutilizando `Marketplace\Order`/`AccrueCommission` tal
 * cual (Merkamigo sigue cobrando su comisión sobre esta venta también).
 */
class SubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithConnectedWompi(): Business
    {
        static $counter = 0;
        $counter++;

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => "Negocio Sub {$counter}"])->business;

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_'.$counter,
            'private_key' => 'prv_test_'.$counter,
            'integrity_secret' => 'integrity-'.$counter,
            'events_secret' => 'events-'.$counter,
            'environment' => 'sandbox',
        ], $owner);

        return $business->fresh(['organization.owner']);
    }

    private function subscriptionProduct(Business $business, string $type = 'producto'): Product
    {
        $product = $business->products()->create([
            'name' => 'Plan Mensual',
            'slug' => 'plan-mensual-'.$business->id,
            'type' => $type,
            'price' => 30000,
            'price_type' => 'exacto',
            'status' => 'publicado',
            'is_available' => true,
        ]);

        app(CreateSubscriptionPlan::class)->handle($product, [
            'frequency' => 'mensual',
            'trial_days' => null,
            'benefits' => 'Acceso completo',
        ]);

        return $product->fresh(['subscriptionPlan', 'business']);
    }

    public function test_creating_a_plan_marks_the_product_as_subscription_sale_type(): void
    {
        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);

        $this->assertSame('suscripcion', $product->sale_type);
        $this->assertTrue($product->isSubscription());
        $this->assertSame('mensual', $product->subscriptionPlan->frequency);
    }

    public function test_subscribing_with_a_trial_does_not_charge_immediately(): void
    {
        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $product->subscriptionPlan->update(['trial_days' => 7]);

        $buyer = User::factory()->create();

        $subscription = app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $this->assertSame(CustomerSubscription::PRUEBA, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame(0, Order::count());
    }

    public function test_subscribing_without_a_trial_charges_the_first_period_immediately(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        $subscription = app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $this->assertSame(CustomerSubscription::ACTIVA, $subscription->status);
        $this->assertNotNull($subscription->current_period_ends_at);
        $this->assertSame(1, Order::count());

        $order = Order::first();
        $this->assertTrue($order->isPaid());
        $this->assertSame($subscription->id, $order->customer_subscription_id);
        $this->assertGreaterThan(0, $order->commission_cents);
    }

    public function test_a_buyer_cannot_subscribe_twice_to_the_same_product(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $this->expectException(ValidationException::class);

        app(SubscribeToProduct::class)->handle($product, 'src_456', 'VISA', '4242', $buyer);
    }

    public function test_renew_customer_subscriptions_charges_subscriptions_past_their_period(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        $subscription = app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);
        $subscription->update(['current_period_ends_at' => now()->subDay()]);

        $count = app(RenewCustomerSubscriptions::class)->handle();

        $this->assertSame(1, $count);
        $this->assertSame(2, Order::count());
        $this->assertTrue($subscription->fresh()->current_period_ends_at->isFuture());
    }

    public function test_renew_customer_subscriptions_skips_subscriptions_not_yet_due(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $count = app(RenewCustomerSubscriptions::class)->handle();

        $this->assertSame(0, $count);
        $this->assertSame(1, Order::count());
    }

    public function test_a_declined_charge_expires_the_subscription(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'DECLINED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $this->assertSame(CustomerSubscription::VENCIDA, CustomerSubscription::first()->status);
    }

    public function test_cancelling_a_subscription_keeps_access_until_period_end(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business);
        $buyer = User::factory()->create();

        $subscription = app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);
        $periodEnd = $subscription->current_period_ends_at;

        $cancelled = app(CancelCustomerSubscription::class)->handle($subscription);

        $this->assertSame(CustomerSubscription::CANCELADA, $cancelled->status);
        $this->assertFalse($cancelled->isUsable());
        $this->assertEquals($periodEnd, $cancelled->current_period_ends_at);
    }

    public function test_a_one_time_digital_purchase_grants_a_permanent_entitlement(): void
    {
        $business = $this->businessWithConnectedWompi();
        $product = $business->products()->create([
            'name' => 'Ebook', 'slug' => 'ebook', 'type' => 'digital',
            'price' => 20000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);
        $buyer = User::factory()->create();

        $order = Order::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'buyer_user_id' => $buyer->id,
            'quantity' => 1,
            'unit_price_cents' => 2000000,
            'amount_cents' => 2000000,
            'reference' => 'MKA-ORD-TEST-DIGITAL',
            'status' => Order::PENDIENTE,
        ]);

        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-digital', []);

        $entitlement = Entitlement::where('user_id', $buyer->id)->where('product_id', $product->id)->first();

        $this->assertNotNull($entitlement);
        $this->assertNull($entitlement->expires_at);
        $this->assertTrue($entitlement->isActive());
    }

    public function test_subscribing_to_a_digital_product_refreshes_the_entitlement_on_each_period_charge(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'wompi-sub-1', 'status' => 'APPROVED']], 200)]);

        $business = $this->businessWithConnectedWompi();
        $product = $this->subscriptionProduct($business, type: 'digital');
        $buyer = User::factory()->create();

        $subscription = app(SubscribeToProduct::class)->handle($product, 'src_123', 'VISA', '4242', $buyer);

        $entitlement = Entitlement::where('user_id', $buyer->id)->where('product_id', $product->id)->first();

        $this->assertNotNull($entitlement);
        $this->assertEquals($subscription->current_period_ends_at, $entitlement->expires_at);
    }

    public function test_the_download_route_is_blocked_without_an_active_entitlement(): void
    {
        $business = $this->businessWithConnectedWompi();
        $product = $business->products()->create([
            'name' => 'Curso', 'slug' => 'curso', 'type' => 'digital',
            'price' => 50000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);
        $product->files()->create(['path' => 'products/1/files/curso.pdf', 'original_name' => 'curso.pdf']);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('products.download', $product))
            ->assertForbidden();
    }
}
