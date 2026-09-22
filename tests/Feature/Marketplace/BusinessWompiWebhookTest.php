<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cada negocio pega su propia URL de webhook en SU cuenta Wompi — se
 * verifica con el `events_secret` de ESE negocio, nunca el de Merkamigo.
 */
class BusinessWompiWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_events_with_an_invalid_signature(): void
    {
        $business = $this->businessWithConnectedWompi('events-secret-real');

        $response = $this->postJson(route('webhooks.wompi.negocio', $business), [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'x', 'status' => 'APPROVED', 'reference' => 'x']],
            'signature' => ['properties' => ['data.transaction.id'], 'checksum' => 'not-the-real-checksum'],
            'timestamp' => now()->timestamp,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_with_a_valid_signature_approves_the_matching_order(): void
    {
        $business = $this->businessWithConnectedWompi('events-secret-real');
        $owner = $business->organization->owner;
        $product = $business->products()->create([
            'name' => 'Producto', 'slug' => 'producto-webhook', 'type' => 'producto',
            'price' => 30000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);
        $buyer = User::factory()->create();

        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);

        $timestamp = now()->timestamp;
        $transactionId = 'wompi-biz-webhook';
        $checksum = hash('sha256', $transactionId.'APPROVED'.$timestamp.'events-secret-real');

        $event = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => $transactionId,
                    'status' => 'APPROVED',
                    'reference' => $order->reference,
                ],
            ],
            'signature' => [
                'properties' => ['data.transaction.id', 'data.transaction.status'],
                'checksum' => $checksum,
            ],
            'timestamp' => $timestamp,
        ];

        $this->postJson(route('webhooks.wompi.negocio', $business), $event)->assertOk();

        $this->assertSame(Order::PAGADO, $order->fresh()->status);
    }

    public function test_a_negocios_webhook_cannot_be_forged_with_another_negocios_secret(): void
    {
        $businessA = $this->businessWithConnectedWompi('secret-a');
        $businessB = $this->businessWithConnectedWompi('secret-b');

        $timestamp = now()->timestamp;
        $checksum = hash('sha256', 'txn'.'APPROVED'.$timestamp.'secret-a');

        $event = [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn', 'status' => 'APPROVED', 'reference' => 'irrelevant']],
            'signature' => ['properties' => ['data.transaction.id', 'data.transaction.status'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
        ];

        // Firmado con el secreto del negocio A, enviado al webhook del negocio B.
        $this->postJson(route('webhooks.wompi.negocio', $businessB), $event)->assertStatus(401);
    }

    private function businessWithConnectedWompi(string $eventsSecret): Business
    {
        static $counter = 0;
        $counter++;

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => "Negocio Webhook {$counter}"])->business;

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
            'events_secret' => $eventsSecret,
            'environment' => 'sandbox',
        ], $owner);

        return $business->fresh(['organization.owner']);
    }
}
