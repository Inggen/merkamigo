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

    public function test_editing_a_field_autosaves_without_an_explicit_save_call(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Autoguardado',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('headline', 'Frase corta autoguardada')
            ->assertSet('savedAt', fn ($savedAt) => $savedAt !== null);

        $this->assertSame('Frase corta autoguardada', $business->fresh()->storefront->headline);
    }

    public function test_autosave_survives_losing_the_permissions_team_context_between_requests(): void
    {
        // En producción, cada interacción de Livewire (autosave, guardar,
        // publicar...) llega por el endpoint genérico `/livewire/update`,
        // que no pasa por el middleware `business.team` de la ruta original
        // — solo `boot()` se ejecuta en cada petición. Simulamos ese
        // "olvido" de contexto entre peticiones fijando el team a otro
        // valor justo antes de editar, tal como quedaría tras una petición
        // AJAX real sin ese middleware.
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Sin Contexto',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id]);

        setPermissionsTeamId(null);
        $owner->unsetRelation('roles');

        $component->set('headline', 'Sobrevive sin contexto de equipo')
            ->assertHasNoErrors();

        $this->assertSame('Sobrevive sin contexto de equipo', $business->fresh()->storefront->headline);
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
