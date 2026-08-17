<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.1.1 del TODO: Inicio de Clientes con municipio, buscador, categorías y
 * negocios destacados.
 */
class ClientesHomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pedido del usuario: el título del hero siempre en dos líneas, una
     * debajo de la otra, en vez de depender del salto de línea natural.
     */
    public function test_the_search_hero_title_breaks_into_two_lines(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Descubre lo mejor de tu municipio.<br>', false)
            ->assertDontSee('Descubre lo mejor de tu municipio. Compra local', false);
    }

    public function test_a_client_without_a_preferred_municipality_is_asked_to_choose_one(): void
    {
        Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $user = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($user)
            ->get(route('clientes.home'))
            ->assertOk()
            ->assertSee('Cajicá')
            ->assertSee(__('Miles de negocios, productos y servicios cerca de ti.'));
    }

    public function test_choosing_a_municipality_persists_it_and_shows_its_published_businesses(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería de Cajicá',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        $user = User::factory()->create(['experience' => 'cliente']);

        $setResponse = $this->actingAs($user)
            ->post(route('clientes.municipio'), ['municipality_id' => $municipality->id]);

        $setResponse->assertRedirect(route('clientes.home'));
        $setResponse->assertPlainCookie('municipio', 'cajica');

        $this->actingAs($user)
            ->withUnencryptedCookie('municipio', 'cajica')
            ->get(route('clientes.home'))
            ->assertOk()
            ->assertSee('Mostrando Cajicá')
            ->assertSee('Panadería de Cajicá')
            ->assertSee('Alimentos');
    }
}
