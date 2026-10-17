<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PR5 de TODO_VENTAS_RENTABILIDAD.md (P1.4): embudo del checkout normal
 * de Marketplace — antes solo existía instrumentación para Live Commerce.
 */
class CheckoutFunnelAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Business, 1: Product}
     */
    private function businessWithProduct(): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Embudo'])->business;

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);
        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_embudo', 'private_key' => 'prv_test_embudo',
            'integrity_secret' => 'integrity-embudo', 'events_secret' => 'events-embudo',
            'environment' => 'sandbox',
        ], $owner);

        $product = $business->products()->create([
            'name' => 'Torta', 'slug' => 'torta-embudo', 'type' => 'producto',
            'price' => 50000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);

        return [$business->fresh(['organization.owner']), $product->fresh()];
    }

    public function test_starting_a_checkout_records_the_checkout_started_event(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();

        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_CHECKOUT_STARTED)->count());
        $event = AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_CHECKOUT_STARTED)->first();
        $this->assertSame($business->id, $event->business_id);
        $this->assertSame($order->id, $event->subject_id);
    }

    public function test_an_approved_payment_records_the_payment_approved_event(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-1', []);

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_APPROVED)->count());
    }

    public function test_a_declined_payment_records_the_payment_failed_event(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        app(ApplyApprovedOrder::class)->handle($order, 'DECLINED', 'txn-1', []);

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_FAILED)->count());
        $this->assertSame(0, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_APPROVED)->count());
    }

    public function test_reapplying_an_already_approved_order_does_not_duplicate_the_event(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-1', []);
        app(ApplyApprovedOrder::class)->handle($order->fresh(), 'APPROVED', 'txn-1', []);

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_APPROVED)->count());
    }

    public function test_reapplying_an_already_declined_order_does_not_duplicate_the_event(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        app(ApplyApprovedOrder::class)->handle($order, 'DECLINED', 'txn-1', []);
        app(ApplyApprovedOrder::class)->handle($order->fresh(), 'DECLINED', 'txn-1', []);

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_FAILED)->count());
    }
}
