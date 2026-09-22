<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Billing\Models\Payment;
use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\AccrueCommission;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ChargeCommission;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Marketplace\Notifications\OrderPaid;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Flujo de marketplace completo (sesión 15 sep 2026): el pago del
 * cliente va directo a la cuenta Wompi del negocio; Merkamigo solo cobra
 * su comisión aparte, contra la tarjeta ya guardada para la suscripción.
 */
class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_is_rejected_if_the_business_has_not_connected_wompi(): void
    {
        [$business, $product] = $this->businessWithProduct(connectWompi: false);
        $buyer = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
    }

    public function test_checkout_computes_amount_and_commission_correctly(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();

        config()->set('services.marketplace.commission_rate', 0.10);

        $order = app(CreateOrderCheckout::class)->handle($product, 2, $buyer);

        $this->assertSame(5000000, $order->unit_price_cents); // $50.000
        $this->assertSame(10000000, $order->amount_cents); // 2 x $50.000
        $this->assertSame(1000000, $order->commission_cents); // 10%
        $this->assertSame(Order::PENDIENTE, $order->status);
    }

    public function test_a_product_without_a_fixed_price_cannot_be_checked_out(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $product->update(['price_type' => 'consultar']);
        $buyer = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
    }

    public function test_approving_an_order_accrues_commission_and_notifies_the_business(): void
    {
        Notification::fake();

        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        $order = app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'wompi-txn-1', ['id' => 'wompi-txn-1']);

        $this->assertSame(Order::PAGADO, $order->status);
        $this->assertNotNull($order->commission_charge_id);

        $charge = CommissionCharge::find($order->commission_charge_id);
        $this->assertSame(1, $charge->orders_count);
        $this->assertSame($order->amount_cents, $charge->gross_amount_cents);
        $this->assertSame($order->commission_cents, $charge->commission_cents);

        $owner = $business->organization->owner;
        Notification::assertSentTo($owner, OrderPaid::class);
    }

    public function test_applying_an_approved_order_twice_is_idempotent(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'wompi-txn-1', []);
        app(ApplyApprovedOrder::class)->handle($order->fresh(), 'APPROVED', 'wompi-txn-1', []);

        $this->assertSame(1, CommissionCharge::first()->orders_count);
    }

    public function test_multiple_paid_orders_accumulate_into_the_same_open_charge(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $orderA = app(CreateOrderCheckout::class)->handle($product, 1, $buyerA);
        app(ApplyApprovedOrder::class)->handle($orderA, 'APPROVED', 'txn-a', []);

        $orderB = app(CreateOrderCheckout::class)->handle($product, 1, $buyerB);
        app(ApplyApprovedOrder::class)->handle($orderB, 'APPROVED', 'txn-b', []);

        $this->assertSame(1, CommissionCharge::count());
        $charge = CommissionCharge::first();
        $this->assertSame(2, $charge->orders_count);
    }

    public function test_charging_the_commission_requires_a_saved_card(): void
    {
        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-1', []);

        $charge = CommissionCharge::first();

        $this->expectException(InvalidArgumentException::class);

        app(ChargeCommission::class)->handle($charge);
    }

    public function test_charging_the_commission_uses_the_businesss_saved_card_and_closes_the_charge(): void
    {
        Http::fake(['*/transactions' => Http::response([
            'data' => ['id' => 'wompi-com-1', 'status' => 'APPROVED'],
        ], 200)]);

        [$business, $product] = $this->businessWithProduct();
        $business->update([
            'wompi_payment_source_id' => '999',
            'auto_renew_enabled' => true,
        ]);

        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-1', []);

        $charge = CommissionCharge::first();
        $payment = app(ChargeCommission::class)->handle($charge);

        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame(CommissionCharge::PAGADA, $charge->fresh()->status);
        $this->assertSame($payment->id, $charge->fresh()->payment_id);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/transactions')
            && $request['amount_in_cents'] === $charge->commission_cents);
    }

    public function test_accruing_zero_commission_charges_still_groups_orders_correctly(): void
    {
        config()->set('services.marketplace.commission_rate', 0.0);

        [$business, $product] = $this->businessWithProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        $charge = app(AccrueCommission::class)->handle($order);

        $this->assertSame(0, $charge->commission_cents);
    }

    /**
     * @return array{0: Business, 1: Product}
     */
    private function businessWithProduct(bool $connectWompi = true): array
    {
        static $counter = 0;
        $counter++;

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => "Midulce Violeta {$counter}"])->business;

        $product = $business->products()->create([
            'name' => "Torta {$counter}",
            'slug' => "torta-{$counter}",
            'type' => 'producto',
            'price' => 50000,
            'price_type' => 'exacto',
            'status' => 'publicado',
            'is_available' => true,
        ]);

        if ($connectWompi) {
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

            Http::fake();
        }

        return [$business->fresh(['organization.owner']), $product->fresh()];
    }
}
