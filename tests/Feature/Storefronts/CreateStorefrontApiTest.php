<?php

namespace Tests\Feature\Storefronts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateStorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_business_via_the_api(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.businesses.store'), [
            'name' => 'Café La Esquina',
            'whatsapp_number' => '+573009998877',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.business.name', 'Café La Esquina')
            ->assertJsonPath('data.storefront.status', 'borrador');

        $this->assertDatabaseHas('businesses', ['name' => 'Café La Esquina']);
    }

    public function test_guests_cannot_create_a_business_via_the_api(): void
    {
        $this->postJson(route('api.v1.businesses.store'), [
            'name' => 'Sin Sesión',
        ])->assertUnauthorized();
    }
}
