<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Platform\Models\WebhookSubscription;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 5.4 del TODO: webhooks salientes firmados para aliados, enganchados
 * dentro de `RecordAuditLog::handle()` sobre el listado curado de
 * eventos externos.
 */
class OutboundWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_wide_subscription_receives_a_signed_webhook_for_a_subscribed_event(): void
    {
        Http::fake(['https://aliado.test/webhook' => Http::response(['ok' => true], 200)]);

        $subscription = WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'un-secreto-de-prueba',
            'subscribed_events' => ['business.published'],
            'is_active' => true,
        ]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Webhook'])->business;

        app(RecordAuditLog::class)->handle($owner, 'business.published', $business);

        Http::assertSent(function ($request) use ($subscription) {
            if ($request->url() !== 'https://aliado.test/webhook') {
                return false;
            }

            $payload = $request->body();
            $expectedSignature = hash_hmac('sha256', $payload, $subscription->secret);

            return $request->hasHeader('X-Merkamigo-Signature', $expectedSignature)
                && str_contains($payload, '"event":"business.published"');
        });
    }

    public function test_a_business_scoped_subscription_only_receives_events_for_its_own_business(): void
    {
        Http::fake(['https://aliado.test/webhook' => Http::response([], 200)]);

        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;
        $ownerB = User::factory()->create();
        $businessB = app(CreateStorefront::class)->handle($ownerB, ['name' => 'Negocio B'])->business;

        WebhookSubscription::create([
            'business_id' => $businessA->id,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['business.published'],
            'is_active' => true,
        ]);

        app(RecordAuditLog::class)->handle($ownerB, 'business.published', $businessB, ['business_id' => $businessB->id]);

        Http::assertNothingSent();

        app(RecordAuditLog::class)->handle($ownerA, 'business.published', $businessA, ['business_id' => $businessA->id]);

        Http::assertSentCount(1);
    }

    public function test_an_unsubscribed_event_type_does_not_trigger_the_webhook(): void
    {
        Http::fake();

        WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['order.completed'],
            'is_active' => true,
        ]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Webhook'])->business;

        app(RecordAuditLog::class)->handle($owner, 'business.published', $business);

        Http::assertNothingSent();
    }

    public function test_an_inactive_subscription_never_fires(): void
    {
        Http::fake();

        WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['business.published'],
            'is_active' => false,
        ]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Webhook'])->business;

        app(RecordAuditLog::class)->handle($owner, 'business.published', $business);

        Http::assertNothingSent();
    }

    public function test_an_action_not_in_the_curated_allowlist_never_triggers_a_webhook(): void
    {
        Http::fake();

        WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['auth.login'],
            'is_active' => true,
        ]);

        $owner = User::factory()->create();

        app(RecordAuditLog::class)->handle($owner, 'auth.login');

        Http::assertNothingSent();
    }

    public function test_a_failed_delivery_is_retried_by_the_queue(): void
    {
        Http::fake(['https://aliado.test/webhook' => Http::response(['error' => 'nope'], 500)]);

        WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['business.published'],
            'is_active' => true,
        ]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Webhook'])->business;

        $this->expectException(\RuntimeException::class);

        app(RecordAuditLog::class)->handle($owner, 'business.published', $business);
    }
}
