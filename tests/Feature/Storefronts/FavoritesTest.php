<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Actions\UpdateProduct;
use App\Livewire\FavoriteButton;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.1.1/1.3 del TODO: guardar o quitar negocios y productos de favoritos.
 */
class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Favorita',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);
        app(UpdateProduct::class)->handle($product, ['status' => 'publicado'], [], [], $owner);

        return $business->fresh();
    }

    public function test_a_client_can_favorite_and_unfavorite_a_business(): void
    {
        $business = $this->publishedBusiness();
        $client = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($client);

        Livewire::test(FavoriteButton::class, ['favoritable' => $business])
            ->assertSet('favorited', false)
            ->call('toggle')
            ->assertSet('favorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $business->getMorphClass(),
            'favoritable_id' => $business->id,
        ]);

        $this->actingAs($client)
            ->get(route('clientes.favoritos'))
            ->assertOk()
            ->assertSee('Panadería Favorita');

        Livewire::test(FavoriteButton::class, ['favoritable' => $business->fresh()])
            ->assertSet('favorited', true)
            ->call('toggle')
            ->assertSet('favorited', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $business->getMorphClass(),
            'favoritable_id' => $business->id,
        ]);
    }

    public function test_a_client_can_favorite_and_unfavorite_a_product(): void
    {
        $business = $this->publishedBusiness();
        $product = $business->products()->firstOrFail();
        $client = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($client);

        Livewire::test(FavoriteButton::class, ['favoritable' => $product])
            ->assertSet('favorited', false)
            ->call('toggle')
            ->assertSet('favorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $product->getMorphClass(),
            'favoritable_id' => $product->id,
        ]);

        $this->actingAs($client)
            ->get(route('clientes.favoritos'))
            ->assertOk()
            ->assertSee('Pan');

        Livewire::test(FavoriteButton::class, ['favoritable' => $product->fresh()])
            ->assertSet('favorited', true)
            ->call('toggle')
            ->assertSet('favorited', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $product->getMorphClass(),
            'favoritable_id' => $product->id,
        ]);
    }

    public function test_the_product_card_shows_a_favorite_button_so_it_can_be_toggled_without_opening_the_product(): void
    {
        $business = $this->publishedBusiness();
        $client = User::factory()->create(['experience' => 'cliente']);

        $response = $this->actingAs($client)
            ->get(route('vitrinas.show', $business))
            ->assertOk();

        // El botón compacto de favorito (solo lo usan las tarjetas de
        // producto en esta página; el de la vitrina en sí no es compacto)
        // debe aparecer una vez por cada producto listado.
        $this->assertGreaterThanOrEqual(
            $business->products()->count(),
            substr_count($response->getContent(), 'Guardar en favoritos'),
        );
    }

    public function test_the_favorite_button_root_element_carries_the_livewire_id_so_clicks_actually_reach_it(): void
    {
        // Regresión: `favorite-button.blade.php` empezaba directo con
        // `@if ($compact)`, y Livewire compila ese `@if` como un
        // comentario HTML pegado (sin salto de línea) a la etiqueta
        // siguiente. El detector de "elemento raíz" de Livewire exige que
        // la etiqueta empiece una línea nueva, así que se saltaba el
        // <button> y anclaba `wire:id`/`wire:snapshot` a un hijo interno
        // (el ícono o el indicador de carga) — el clic en el botón real
        // nunca llegaba a Livewire porque `wire:id` no era un ancestro
        // suyo. Este test verifica la relación de orden real en el HTML:
        // `wire:id` debe aparecer ANTES que `wire:click="toggle"`, lo que
        // solo pasa si `wire:id` está en un elemento que envuelve al
        // botón (no al revés).
        $business = $this->publishedBusiness();
        $client = User::factory()->create(['experience' => 'cliente']);

        $html = $this->actingAs($client)
            ->get(route('vitrinas.show', $business))
            ->assertOk()
            ->getContent();

        $wireIdPosition = strpos($html, 'wire:id="');
        $wireClickPosition = strpos($html, 'wire:click="toggle"');

        $this->assertNotFalse($wireIdPosition);
        $this->assertNotFalse($wireClickPosition);
        $this->assertLessThan(
            $wireClickPosition,
            $wireIdPosition,
            'wire:id debe aparecer antes que wire:click="toggle" en el HTML: si no, wire:id quedó en un elemento anidado dentro del botón en vez de envolverlo, y el clic nunca llega a Livewire.',
        );
    }

    public function test_a_guest_is_redirected_to_login_when_trying_to_favorite(): void
    {
        $business = $this->publishedBusiness();

        Livewire::test(FavoriteButton::class, ['favoritable' => $business])
            ->call('toggle')
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
