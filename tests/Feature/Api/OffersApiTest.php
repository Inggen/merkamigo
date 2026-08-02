<?php

namespace Tests\Feature\Api;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 5.1/2.2 del TODO: propuestas de un negocio a una necesidad vía API.
 */
class OffersApiTest extends TestCase
{
    use RefreshDatabase;

    private function openNeed(): Need
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $need = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesito un electricista', 'description' => 'Urgente.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        return $need->fresh();
    }

    public function test_a_business_owner_can_submit_an_offer_to_a_need(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Electricistas Zipa'])->business;
        Sanctum::actingAs($owner);

        $need = $this->openNeed();

        $response = $this->postJson(route('api.v1.businesses.needs.offers.store', [$business, $need]), [
            'message' => 'Puedo ayudarte hoy mismo.',
            'price' => 50000,
            'availability' => 'Hoy en la tarde',
        ]);

        $response->assertCreated()->assertJsonPath('data.message', 'Puedo ayudarte hoy mismo.');
        $this->assertDatabaseHas('offers', ['need_id' => $need->id, 'business_id' => $business->id]);
    }

    public function test_someone_outside_the_business_cannot_submit_an_offer_on_its_behalf(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Electricistas Zipa'])->business;
        $need = $this->openNeed();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.businesses.needs.offers.store', [$business, $need]), ['message' => 'x'])
            ->assertForbidden();
    }

    public function test_a_business_can_list_its_own_offers(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Electricistas Zipa'])->business;
        Sanctum::actingAs($owner);
        $need = $this->openNeed();

        $this->postJson(route('api.v1.businesses.needs.offers.store', [$business, $need]), ['message' => 'Puedo ayudarte.']);

        $this->getJson(route('api.v1.businesses.offers.index', $business))
            ->assertOk()
            ->assertJsonPath('data.0.need_id', $need->id);
    }

    public function test_a_business_can_withdraw_its_own_offer(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Electricistas Zipa'])->business;
        Sanctum::actingAs($owner);
        $need = $this->openNeed();

        $offerId = $this->postJson(route('api.v1.businesses.needs.offers.store', [$business, $need]), ['message' => 'Puedo ayudarte.'])
            ->json('data.id');

        $this->deleteJson(route('api.v1.offers.destroy', $offerId))
            ->assertOk()
            ->assertJsonPath('data.status', Offer::RETIRADA);
    }

    public function test_a_business_cannot_withdraw_another_businesss_offer(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;
        Sanctum::actingAs($ownerA);
        $need = $this->openNeed();
        $offerId = $this->postJson(route('api.v1.businesses.needs.offers.store', [$businessA, $need]), ['message' => 'Puedo ayudarte.'])
            ->json('data.id');

        $ownerB = User::factory()->create();
        app(CreateStorefront::class)->handle($ownerB, ['name' => 'Negocio B']);
        Sanctum::actingAs($ownerB);

        $this->deleteJson(route('api.v1.offers.destroy', $offerId))->assertForbidden();
    }
}
