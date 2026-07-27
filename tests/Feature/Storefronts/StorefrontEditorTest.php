<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.6 del TODO: editor de vitrina del panel (secciones, publicar/despublicar).
 */
class StorefrontEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_and_publish_from_the_editor(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Editor',
            'whatsapp_number' => '+573001112233',
        ])->business;

        app(CreateProduct::class)->handle($business, [
            'name' => 'Servicio X', 'type' => 'servicio', 'price_type' => 'consultar',
        ], [], $owner);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('description', 'Descripción completa del negocio.')
            ->set('category_id', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Descripción completa del negocio.', $business->fresh()->storefront->description);

        $component = Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id]);

        // Falta categoría y municipio: no debe publicar todavía.
        $component->call('publish');
        $this->assertSame('borrador', $business->fresh()->status);
        $component->assertSet('missing', fn ($missing) => count($missing) > 0);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_editor(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $businessA->id])
            ->assertForbidden();
    }
}
