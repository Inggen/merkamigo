<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderCheckoutHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_checkout_signs_the_order_with_the_businesss_own_key_not_merkamigos(): void
    {
        [$business, $product] = $this->businessWithConnectedWompi();
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)->get(route('marketplace.checkout.create', $product));

        $response->assertOk();
        $response->assertSee('pub_test_business', false);

        $order = Order::where('product_id', $product->id)->firstOrFail();
        $expectedSignature = hash('sha256', $order->reference.$order->amount_cents.'COP'.'integrity-secret-business');
        $response->assertSee($expectedSignature, false);
    }

    public function test_a_guest_is_redirected_to_login_before_checkout(): void
    {
        [$business, $product] = $this->businessWithConnectedWompi();

        $this->get(route('marketplace.checkout.create', $product))->assertRedirect(route('login'));
    }

    public function test_returning_with_an_approved_transaction_marks_the_order_paid(): void
    {
        [$business, $product] = $this->businessWithConnectedWompi();
        $buyer = User::factory()->create();

        $order = Order::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'buyer_user_id' => $buyer->id,
            'quantity' => 1,
            'unit_price_cents' => 5000000,
            'amount_cents' => 5000000,
            'commission_cents' => 250000,
            'reference' => 'MKA-ORD-TEST-1',
            'status' => Order::PENDIENTE,
        ]);

        Http::fake(['*/transactions/*' => Http::response([
            'data' => ['id' => 'wompi-order-txn', 'status' => 'APPROVED', 'reference' => $order->reference],
        ], 200)]);

        $response = $this->actingAs($buyer)->get(route('marketplace.checkout.return', $order).'?id=wompi-order-txn');

        $response->assertOk();
        $this->assertSame(Order::PAGADO, $order->fresh()->status);
    }

    public function test_a_stranger_cannot_view_someone_elses_order_return_page(): void
    {
        [$business, $product] = $this->businessWithConnectedWompi();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();

        $order = Order::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'buyer_user_id' => $buyer->id,
            'quantity' => 1,
            'unit_price_cents' => 5000000,
            'amount_cents' => 5000000,
            'reference' => 'MKA-ORD-TEST-2',
            'status' => Order::PENDIENTE,
        ]);

        $this->actingAs($stranger)
            ->get(route('marketplace.checkout.return', $order))
            ->assertForbidden();
    }

    /**
     * @return array{0: Business, 1: Product}
     */
    private function businessWithConnectedWompi(): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio HTTP'])->business;

        $product = $business->products()->create([
            'name' => 'Producto HTTP',
            'slug' => 'producto-http',
            'type' => 'producto',
            'price' => 50000,
            'price_type' => 'exacto',
            'status' => 'publicado',
            'is_available' => true,
        ]);

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_business',
            'private_key' => 'prv_test_business',
            'integrity_secret' => 'integrity-secret-business',
            'events_secret' => 'events-secret-business',
            'environment' => 'sandbox',
        ], $owner);

        return [$business->fresh(), $product->fresh()];
    }
}
