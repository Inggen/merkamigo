<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.7 del TODO: Copiloto de WhatsApp inicial.
 */
class WhatsAppCopilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_a_product_promotion_with_a_valid_link_and_no_invented_price(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Panadería Copiloto'])->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2500,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'promocion')
            ->set('productId', $product->id)
            ->set('tone', 'cercano')
            ->call('generate')
            ->assertSet('hasGenerated', true)
            ->assertSet('generated', fn ($text) => str_contains($text, 'Pan francés')
                && str_contains($text, '$2.500')
                && str_contains($text, route('vitrinas.product', [$business, $product])));
    }

    public function test_generating_without_a_product_never_invents_a_price(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Producto'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'promocion')
            ->set('tone', 'formal')
            ->call('generate')
            ->assertSet('generated', fn ($text) => ! str_contains($text, '$'));
    }

    public function test_owner_can_save_a_draft_and_it_appears_in_history(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Historial'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'estado')
            ->set('tone', 'cercano')
            ->call('generate')
            ->call('saveDraft')
            ->assertSee('Historial');

        $this->assertDatabaseHas('whatsapp_contents', [
            'business_id' => $business->id,
            'type' => 'estado',
        ]);
    }

    public function test_history_is_capped_at_twenty_drafts_per_business(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Tope'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id]);

        foreach (range(1, 25) as $i) {
            $component->set('type', 'estado')->call('generate')->call('saveDraft');
        }

        $this->assertSame(20, WhatsAppContent::where('business_id', $business->id)->count());
    }

    public function test_a_collaborator_of_another_business_cannot_use_the_copilot(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $businessA->id])
            ->assertForbidden();
    }

    public function test_the_copilot_survives_losing_the_permissions_team_context_between_requests(): void
    {
        // Mismo patrón de regresión que en el editor de vitrina: boot() debe
        // restablecer el contexto de equipo en cada petición AJAX, no solo
        // en la carga inicial (ver commit del fix del 403).
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Contexto'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id]);

        setPermissionsTeamId(null);
        $owner->unsetRelation('roles');

        $component->set('type', 'presentacion')
            ->call('generate')
            ->assertHasNoErrors()
            ->call('saveDraft');

        $this->assertDatabaseHas('whatsapp_contents', [
            'business_id' => $business->id,
            'type' => 'presentacion',
        ]);
    }
}
