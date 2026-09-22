<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * 1.1.1 del TODO: municipio, buscador, categorías y negocios destacados.
 * Vivía en Inicio (`/`); desde la sesión 15 sep 2026 esto es `Explorar`
 * (`/explorar`) — el feed social pasó a ser Inicio (2.1 del TODO social).
 */
class ClientesHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_header_matches_the_client_navigation(): void
    {
        $user = User::factory()->create(['name' => 'Valentina Pérez']);
        $this->actingAs($user);

        $header = Blade::render('<x-cliente-nav />');

        $this->assertStringContainsString(route('explorar'), $header);
        $this->assertStringContainsString(route('clientes.actividad'), $header);
        $this->assertStringContainsString(__('Mensajes'), $header);
        $this->assertStringContainsString(route('clientes.favoritos'), $header);
        $this->assertSame(1, substr_count($header, route('clientes.favoritos')));
        $this->assertStringContainsString(__('Hola,'), $header);
        $this->assertStringContainsString('Valentina', $header);
        $this->assertStringNotContainsString(route('reels'), $header);
        $this->assertStringNotContainsString(route('mis-solicitudes'), $header);
        $this->assertStringNotContainsString('type="search"', $header);
        $this->assertStringNotContainsString(__('Cerca de mí'), $header);
    }

    /**
     * Pedido del usuario: el título del hero siempre en dos líneas, una
     * debajo de la otra, en vez de depender del salto de línea natural.
     */
    public function test_the_search_hero_title_breaks_into_two_lines(): void
    {
        $this->get(route('explorar'))
            ->assertOk()
            ->assertSee(route('feed'), false)
            ->assertSee(__('Publicaciones'))
            ->assertSee('Descubre lo mejor de tu municipio.<br>', false)
            ->assertDontSee('Descubre lo mejor de tu municipio. Compra local', false);
    }

    /**
     * Pedido del usuario: la fila de categorías en una sola línea (sin
     * envolver en grilla), con un botón "Ver más" que despliega las que no
     * caben — solo las primeras `visibleCount` (8 por defecto) se ven
     * directo en la fila.
     */
    public function test_the_category_row_shows_a_dropdown_for_categories_beyond_the_visible_count(): void
    {
        foreach (range(1, 10) as $i) {
            Category::create(['name' => "Categoría {$i}", 'slug' => "categoria-{$i}", 'is_active' => true]);
        }

        $response = $this->get(route('explorar'));

        $response->assertOk()
            ->assertSee(__('Ver más'))
            ->assertSee('Categoría 1')
            ->assertSee('Categoría 8');

        // Las últimas 2 (9 y 10) solo deben estar dentro del desplegable
        // "Ver más", no en la fila visible.
        $inlineCount = substr_count($response->getContent(), 'aria-label="Ver Categoría 9"');
        $this->assertSame(0, $inlineCount);
    }

    /**
     * Pedido del usuario: "Ver más" siempre debe estar — sin categorías
     * de sobra es un enlace directo a la página completa de categorías,
     * no un desplegable vacío.
     */
    public function test_the_category_row_shows_ver_mas_as_a_plain_link_when_everything_fits(): void
    {
        Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $this->get(route('explorar'))
            ->assertOk()
            ->assertSee(__('Ver más'))
            ->assertSee(route('categorias'), false);
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
