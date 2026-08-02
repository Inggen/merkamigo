<?php

namespace Tests\Feature\Api;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Identity\Models\UserDevice;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Needs\Notifications\OfferSubmitted;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 5.2 del TODO: registro de dispositivos, preferencias de notificación y
 * el canal push (sandbox de FCM — nunca un envío real).
 */
class DevicesAndPushApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_revoke_a_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.devices.store'), [
            'platform' => 'fcm',
            'push_token' => 'token-abc',
        ])->assertCreated();

        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'push_token' => 'token-abc']);

        $deviceId = $response->json('data.id');

        $this->deleteJson(route('api.v1.devices.destroy', $deviceId))->assertOk();
        $this->assertDatabaseMissing('user_devices', ['id' => $deviceId]);
    }

    public function test_registering_the_same_token_twice_reassigns_it_instead_of_duplicating(): void
    {
        $userA = User::factory()->create();
        Sanctum::actingAs($userA);
        $this->postJson(route('api.v1.devices.store'), ['platform' => 'fcm', 'push_token' => 'shared-token'])->assertCreated();

        $userB = User::factory()->create();
        Sanctum::actingAs($userB);
        $this->postJson(route('api.v1.devices.store'), ['platform' => 'fcm', 'push_token' => 'shared-token'])->assertCreated();

        $this->assertSame(1, UserDevice::where('push_token', 'shared-token')->count());
        $this->assertDatabaseHas('user_devices', ['push_token' => 'shared-token', 'user_id' => $userB->id]);
    }

    public function test_a_user_cannot_revoke_someone_elses_device(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $deviceId = $this->postJson(route('api.v1.devices.store'), ['platform' => 'fcm', 'push_token' => 'x'])->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('api.v1.devices.destroy', $deviceId))->assertForbidden();
    }

    public function test_the_push_channel_calls_fcm_for_each_registered_device_and_isolates_failures(): void
    {
        Http::fake([
            'fcm.test/*' => Http::sequence()
                ->push(['success' => 1], 200)
                ->push(['error' => 'invalid token'], 400),
        ]);

        $user = User::factory()->create();
        UserDevice::create(['user_id' => $user->id, 'platform' => 'fcm', 'push_token' => 'good-token']);
        UserDevice::create(['user_id' => $user->id, 'platform' => 'fcm', 'push_token' => 'bad-token']);

        $user->notify(new OfferSubmitted($this->makeOffer($user)));

        Http::assertSentCount(2);
    }

    public function test_a_user_can_disable_push_for_a_notification_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson(route('api.v1.notificaciones.preferencias'), [
            'push_disabled' => ['offer_submitted'],
        ])->assertOk()->assertJsonPath('data.push_disabled.0', 'offer_submitted');

        $user->refresh();
        $this->assertTrue($user->hasDisabledPushFor(OfferSubmitted::class));

        Http::fake();
        UserDevice::create(['user_id' => $user->id, 'platform' => 'fcm', 'push_token' => 'x']);
        $user->notify(new OfferSubmitted($this->makeOffer($user)));

        Http::assertNothingSent();
    }

    private function makeOffer(User $buyer): Offer
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true,
        ]);
        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito algo', 'description' => 'x', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Push'])->business;

        return app(SubmitOffer::class)->handle($business, $need, ['message' => 'Puedo ayudarte'], $owner);
    }
}
