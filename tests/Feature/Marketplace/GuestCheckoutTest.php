<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Marketplace\Notifications\GuestOrderPaid;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Models\Entitlement;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): checkout de invitado, solo
 * para productos digitales y detrás de
 * `services.marketplace.guest_checkout_enabled` (decisión del usuario
 * 2026-10-09: sin modelo de inventario todavía, los físicos siguen
 * exigiendo cuenta).
 */
class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_as_guest_is_rejected_when_the_flag_is_off(): void
    {
        [$business, $product] = $this->digitalProduct();

        $this->get(route('marketplace.checkout.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_as_guest_is_rejected_for_a_physical_product_even_with_the_flag_on(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Físico'])->business;
        $this->connectWompi($business, $owner);
        $product = $business->products()->create([
            'name' => 'Torta', 'slug' => 'torta-fisica', 'type' => 'producto',
            'price' => 30000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);

        $this->get(route('marketplace.checkout.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_the_guest_form_shows_up_for_a_digital_product_with_the_flag_on(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();

        $this->get(route('marketplace.checkout.create', $product))
            ->assertOk()
            ->assertSee('guest_email', false);
    }

    public function test_a_logged_in_buyer_is_unaffected_by_the_guest_flow(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->getJson(route('marketplace.checkout.create', $product))
            ->assertOk()
            ->assertJsonPath('redirectUrl', route('marketplace.checkout.return', Order::first()));
    }

    public function test_a_guest_can_submit_contact_details_and_get_a_signed_return_url(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();

        $response = $this->postJson(route('marketplace.guest.checkout.create', $product), [
            'guest_name' => 'Carlos Pérez',
            'guest_email' => 'carlos@example.com',
            'guest_phone' => '3001234567',
        ])->assertOk();

        $order = Order::firstOrFail();
        $this->assertNull($order->buyer_user_id);
        $this->assertSame('Carlos Pérez', $order->guest_name);
        $this->assertSame('carlos@example.com', $order->guest_email);
        $this->assertTrue($order->isGuestOrder());

        $response->assertJsonPath('reference', $order->reference);
        $this->assertStringContainsString('signature=', $response->json('redirectUrl'));
    }

    /**
     * Revisión final del TODO (§13.1 de la auditoría): un invitado que
     * reenvía el mismo formulario (doble clic, refresh) tampoco debe
     * terminar con dos pedidos.
     */
    public function test_a_guest_resubmitting_the_same_form_reuses_the_pending_order(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();

        $payload = [
            'guest_name' => 'Carlos Pérez',
            'guest_email' => 'carlos@example.com',
            'guest_phone' => '3001234567',
        ];

        $first = $this->postJson(route('marketplace.guest.checkout.create', $product), $payload)->assertOk();
        $second = $this->postJson(route('marketplace.guest.checkout.create', $product), $payload)->assertOk();

        $this->assertSame(1, Order::count());
        $this->assertSame($first->json('reference'), $second->json('reference'));
    }

    public function test_submitting_guest_checkout_for_a_physical_product_is_rejected_even_if_the_route_is_hit_directly(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Físico 2'])->business;
        $this->connectWompi($business, $owner);
        $product = $business->products()->create([
            'name' => 'Torta', 'slug' => 'torta-fisica-2', 'type' => 'producto',
            'price' => 30000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);

        $this->postJson(route('marketplace.guest.checkout.create', $product), [
            'guest_name' => 'Carlos', 'guest_email' => 'c@example.com', 'guest_phone' => '300',
        ])->assertNotFound();

        $this->assertSame(0, Order::count());
    }

    public function test_create_order_checkout_rejects_a_guest_when_the_flag_is_off_even_if_called_directly(): void
    {
        [$business, $product] = $this->digitalProduct();

        $this->expectException(InvalidArgumentException::class);

        app(CreateOrderCheckout::class)->handle($product, 1, null, null, null, 'Ana', 'ana@example.com', '300');
    }

    public function test_returning_as_guest_with_an_approved_transaction_marks_the_order_paid_and_emails_confirmation(): void
    {
        Notification::fake();
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();

        $order = app(CreateOrderCheckout::class)->handle($product, 1, null, null, null, 'Ana Gómez', 'ana@example.com', '3009998888');

        Http::fake(['*/transactions/*' => Http::response([
            'data' => ['id' => 'wompi-guest-1', 'status' => 'APPROVED', 'reference' => $order->reference],
        ], 200)]);

        $signedUrl = URL::signedRoute('marketplace.guest.checkout.return', ['order' => $order->id]);

        $response = $this->get($signedUrl.'&id=wompi-guest-1');

        $response->assertOk();
        $this->assertSame(Order::PAGADO, $order->fresh()->status);
        $response->assertSee('Descargar', false);

        Notification::assertSentOnDemand(
            GuestOrderPaid::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ana@example.com',
        );

        // Sin cuenta, no se crea ningún Entitlement (hallazgo del PR3:
        // `entitlements.user_id` no acepta null).
        $this->assertSame(0, Entitlement::count());
    }

    public function test_a_tampered_guest_return_url_is_rejected(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, null, null, null, 'Ana', 'ana@example.com', '300');

        $this->get(route('marketplace.guest.checkout.return', ['order' => $order->id]))
            ->assertForbidden();
    }

    public function test_the_guest_cannot_download_before_paying(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, null, null, null, 'Ana', 'ana@example.com', '300');

        $this->get(URL::signedRoute('marketplace.guest.download', ['order' => $order->id]))
            ->assertForbidden();
    }

    public function test_the_guest_can_download_once_paid(): void
    {
        Storage::fake('private');
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();
        Storage::disk('private')->put('products/guest/curso.pdf', 'contenido');

        $order = app(CreateOrderCheckout::class)->handle($product, 1, null, null, null, 'Ana', 'ana@example.com', '300');
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-guest', []);

        $this->get(URL::signedRoute('marketplace.guest.download', ['order' => $order->id]))
            ->assertOk();
    }

    public function test_a_registered_buyers_order_cannot_be_downloaded_through_the_guest_route(): void
    {
        config()->set('services.marketplace.guest_checkout_enabled', true);
        [$business, $product] = $this->digitalProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-registered', []);

        $this->get(URL::signedRoute('marketplace.guest.download', ['order' => $order->id]))
            ->assertForbidden();
    }

    /**
     * Decisión #6 escalada con el usuario (2026-10-09): borrar la
     * cuenta de un comprador ya no borra en cascada su historial de
     * ventas — el pedido sobrevive sin comprador identificado.
     */
    public function test_deleting_a_buyers_account_no_longer_deletes_their_order_history(): void
    {
        [$business, $product] = $this->digitalProduct();
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        $buyer->delete();

        $this->assertNotNull($order->fresh());
        $this->assertNull($order->fresh()->buyer_user_id);
    }

    /**
     * @return array{0: Business, 1: Product}
     */
    private function digitalProduct(): array
    {
        static $counter = 0;
        $counter++;

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => "Negocio Digital {$counter}"])->business;
        $this->connectWompi($business, $owner, $counter);

        $product = $business->products()->create([
            'name' => "Curso {$counter}",
            'slug' => "curso-{$counter}",
            'type' => 'digital',
            'price' => 50000,
            'price_type' => 'exacto',
            'status' => 'publicado',
            'is_available' => true,
        ]);
        $product->files()->create(['path' => 'products/guest/curso.pdf', 'original_name' => 'curso.pdf']);

        return [$business->fresh(['organization.owner']), $product->fresh()];
    }

    private function connectWompi(Business $business, User $owner, int $counter = 0): void
    {
        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_'.$business->id.'_'.$counter,
            'private_key' => 'prv_test_'.$business->id.'_'.$counter,
            'integrity_secret' => 'integrity-'.$business->id.'_'.$counter,
            'events_secret' => 'events-'.$business->id.'_'.$counter,
            'environment' => 'sandbox',
        ], $owner);

        // Sin el reset `Http::fake()` (sin argumentos) de
        // `OrderCheckoutTest::businessWithProduct()` — acá SÍ se
        // necesita un `Http::fake(['*/transactions/*' => ...])` real
        // después, para las pruebas que golpean la ruta HTTP de verdad
        // (`guestReturn`) en vez de llamar `ApplyApprovedOrder`
        // directamente; un `Http::fake()` catch-all registrado antes
        // le gana al patrón específico de la prueba.
    }
}
