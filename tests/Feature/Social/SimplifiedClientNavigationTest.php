<?php

namespace Tests\Feature\Social;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\ManageLiveStream;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Navegación principal del cliente: cuatro secciones centradas en el
 * encabezado y contenido sin sidebar lateral.
 */
class SimplifiedClientNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_header_shows_the_four_primary_sections_without_a_left_sidebar(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Vitrinas');
        $response->assertSee('Comunidad');
        $response->assertSee('Eventos');
        $response->assertSee('Merkapuntos');
        $response->assertDontSee('aria-label="Explorar Merkamigo"', false);

        // Los 7 accesos de categoría (simples búsquedas por nombre) ya
        // no deben aparecer como enlaces del sidebar.
        $response->assertDontSee('Restaurantes');
        $response->assertDontSee('Digitales');
        $response->assertDontSee('Suscripciones');
    }

    public function test_live_index_page_lists_live_upcoming_and_replay_streams(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $liveProducts = $business->products()->pluck('id')->all();

        $live = app(ManageLiveStream::class)->create($business, [
            'title' => 'Venta en vivo de hoy',
            'product_ids' => $liveProducts,
        ], $owner);
        $live = app(ManageLiveStream::class)->start($live, $owner);

        $upcoming = app(ManageLiveStream::class)->create($business, [
            'title' => 'Lanzamiento la próxima semana',
            'scheduled_at' => now()->addWeek(),
            'product_ids' => $liveProducts,
        ], $owner);

        $finished = app(ManageLiveStream::class)->create($business, [
            'title' => 'Transmisión de la semana pasada',
            'product_ids' => $liveProducts,
        ], $owner);
        $finished = app(ManageLiveStream::class)->start($finished, $owner);
        $finished = app(ManageLiveStream::class)->end($finished, 'https://www.youtube.com/watch?v=abcdefghijk', $owner);

        $response = $this->get(route('live.index'));

        $response->assertOk();
        $response->assertSee('En vivo ahora');
        $response->assertSee($live->title);
        $response->assertSee('Próximos eventos');
        $response->assertSee($upcoming->title);
        $response->assertSee('Replays recientes');
        $response->assertSee($finished->title);
    }

    public function test_live_index_page_shows_empty_state_without_streams(): void
    {
        $response = $this->get(route('live.index'));

        $response->assertOk();
        $response->assertSee('Todavía no hay transmisiones en vivo');
    }

    public function test_merkapuntos_requires_authentication(): void
    {
        // "Merkapuntos" (TODO_Merkapuntos.md, F2.4) es la sección personal
        // de puntos del cliente — a diferencia del antiguo placeholder
        // "Recompensas", ahora exige sesión y manda al login conservando
        // el destino.
        $response = $this->get(route('merkapuntos'));

        $response->assertRedirect(route('login'));
    }

    public function test_internal_client_pages_use_the_header_without_a_left_sidebar(): void
    {
        $customer = User::factory()->create(['experience' => 'cliente']);

        $response = $this->actingAs($customer)->get(route('messages.index'));

        $response->assertOk();
        $response->assertSee('Vitrinas');
        $response->assertSee('Comunidad');
        $response->assertSee('data-mobile-bottom-nav-reserve', false);
        $response->assertDontSee('aria-label="Explorar Merkamigo"', false);
    }

    public function test_mobile_navigation_uses_the_four_primary_sections_and_the_central_pidelo_action(): void
    {
        $customer = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($customer);
        $html = Blade::render('<x-cliente-bottom-nav />');

        $this->assertStringContainsString('data-client-bottom-nav', $html);
        $this->assertStringContainsString('Vitrinas', $html);
        $this->assertStringContainsString('Comunidad', $html);
        $this->assertStringContainsString('Pídelo', $html);
        $this->assertStringContainsString('Eventos', $html);
        $this->assertStringContainsString('Merkapuntos', $html);
        $this->assertStringNotContainsString('Mensajes', $html);
        $this->assertStringNotContainsString('Favoritos', $html);
        $this->assertStringNotContainsString('Perfil', $html);
    }

    public function test_mobile_chatbot_stays_above_the_client_navigation(): void
    {
        $customer = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($customer);
        $html = Blade::render('<x-storefront-chat-widget />');

        $this->assertStringContainsString('bottom-[calc(4.5rem+env(safe-area-inset-bottom))]', $html);
    }

    public function test_the_left_sidebar_does_not_appear_on_public_pages(): void
    {
        $customer = User::factory()->create(['experience' => 'cliente']);

        // `/pidelo` (el listado público) no pasó `showSidebar: true` —
        // sigue siendo una vista de descubrimiento alcanzable por invitados,
        // no una vista interna de cuenta.
        $response = $this->actingAs($customer)->get(route('pidelo'));

        $response->assertOk();
        $response->assertDontSee('aria-label="Explorar Merkamigo"', false);
    }

    public function test_the_left_sidebar_is_removed_from_pidelo_nueva_and_mis_solicitudes(): void
    {
        $customer = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($customer)->get(route('pidelo.nueva'))
            ->assertOk()
            ->assertDontSee('aria-label="Explorar Merkamigo"', false);

        $this->actingAs($customer)->get(route('mis-solicitudes'))
            ->assertOk()
            ->assertDontSee('aria-label="Explorar Merkamigo"', false);
    }

    private function publishedBusiness(): Business
    {
        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'alimentos'],
            ['name' => 'Alimentos', 'is_active' => true],
        );
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Eventos',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        $plan = Plan::firstOrCreate(
            ['slug' => 'negocios'],
            [
                'name' => 'Negocios', 'price_cents' => 9900000, 'billing_period' => Plan::MENSUAL,
                'is_active' => true, 'position' => 2,
            ],
        );
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        $business->products()->create([
            'name' => 'Producto de prueba',
            'slug' => 'producto-de-prueba-'.$business->id,
            'price_cents' => 1000000,
            'status' => 'publicado',
            'category_id' => $category->id,
        ]);

        return $business;
    }
}
