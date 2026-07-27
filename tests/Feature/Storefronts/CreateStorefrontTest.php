<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prueba el criterio de aceptación de 0.4: la misma acción `CreateStorefront`
 * se invoca desde una prueba, un componente Livewire y (en
 * CreateStorefrontApiTest) `POST /api/v1/businesses`, sin duplicar reglas.
 */
class CreateStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_business_storefront_membership_and_owner_role(): void
    {
        $user = User::factory()->create();

        $storefront = app(CreateStorefront::class)->handle($user, [
            'name' => 'Panadería Doña Ana',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => null,
            'category_id' => null,
            'headline' => null,
            'description' => null,
        ]);

        $business = $storefront->business;

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'name' => 'Panadería Doña Ana',
            'status' => 'borrador',
        ]);

        $this->assertDatabaseHas('storefronts', [
            'business_id' => $business->id,
            'status' => 'borrador',
        ]);

        $this->assertDatabaseHas('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'status' => 'activo',
        ]);

        setPermissionsTeamId($business->id);
        $this->assertTrue($user->fresh()->hasRole('owner'));
    }

    public function test_the_emprendedor_livewire_page_uses_the_same_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::emprendedores.crear-vitrina')
            ->set('name', 'Frutas y Verduras El Sol')
            ->set('whatsapp_number', '+573004445566')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('businesses', [
            'name' => 'Frutas y Verduras El Sol',
        ]);
    }
}
