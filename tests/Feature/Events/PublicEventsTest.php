<?php

namespace Tests\Feature\Events;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Agenda pública de eventos (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 5) — slice mínimo que reemplazó el antiguo `/eventos` de
 * LiveStream (ver SimplifiedClientNavigationTest, que ahora cubre
 * `/en-vivo`).
 */
class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_page_shows_an_empty_state_without_published_events(): void
    {
        $response = $this->get(route('eventos'));

        $response->assertOk();
        $response->assertSee('images/backgrounds/fondo_banner_eventos.webp', false);
        $response->assertSee('Eventos');
        $response->assertSee('destacados');
        $response->assertSee('Todos los municipios');
        $response->assertSee('Todas las categorías');
        $response->assertSee('Todavía no hay eventos publicados');
    }

    public function test_the_index_page_lists_published_upcoming_events_only(): void
    {
        $business = $this->publishedBusiness();

        $published = $this->createEvent($business, [
            'title' => 'Noche de música y café',
            'status' => PublicEvent::PUBLICADO,
            'starts_at' => now()->addDays(5),
        ]);

        $this->createEvent($business, [
            'title' => 'Borrador sin publicar',
            'status' => PublicEvent::BORRADOR,
            'starts_at' => now()->addDays(5),
        ]);

        $this->createEvent($business, [
            'title' => 'Evento ya pasado',
            'status' => PublicEvent::PUBLICADO,
            'starts_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('eventos'));

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee('Borrador sin publicar');
        $response->assertDontSee('Evento ya pasado');
    }

    public function test_the_index_page_filters_by_municipality(): void
    {
        $zipaquira = Municipality::firstOrCreate(
            ['slug' => 'zipaquira'],
            ['name' => 'Zipaquirá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $cajica = Municipality::firstOrCreate(
            ['slug' => 'cajica-eventos'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );

        $business = $this->publishedBusiness();

        $inZipaquira = $this->createEvent($business, [
            'title' => 'Evento en Zipaquirá',
            'status' => PublicEvent::PUBLICADO,
            'starts_at' => now()->addDays(2),
            'municipality_id' => $zipaquira->id,
        ]);

        $inCajica = $this->createEvent($business, [
            'title' => 'Evento en Cajicá',
            'status' => PublicEvent::PUBLICADO,
            'starts_at' => now()->addDays(2),
            'municipality_id' => $cajica->id,
        ]);

        $response = $this->get(route('eventos', ['municipio' => 'zipaquira']));

        $response->assertOk();
        $response->assertSee($inZipaquira->title);
        $response->assertDontSee($inCajica->title);
    }

    public function test_the_show_page_displays_a_published_event_with_reservation_and_internal_contact(): void
    {
        $business = $this->publishedBusiness();

        $event = $this->createEvent($business, [
            'title' => 'Noche de música y café',
            'status' => PublicEvent::PUBLICADO,
            'starts_at' => now()->addDays(5),
            'description' => 'Una velada íntima con talentos locales.',
        ]);

        $response = $this->get(route('eventos.show', $event));

        $response->assertOk();
        $response->assertSee($event->title);
        $response->assertSee($business->name);
        $response->assertSee('Acerca del evento');
        $response->assertSee('Reserva tu cupo');
        $response->assertSee(route('vitrinas.contact.internal', $business), false);
    }

    public function test_an_unpublished_event_is_not_reachable_by_its_slug(): void
    {
        $business = $this->publishedBusiness();

        $event = $this->createEvent($business, [
            'title' => 'Todavía en borrador',
            'status' => PublicEvent::BORRADOR,
        ]);

        $this->get(route('eventos.show', $event))->assertNotFound();
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
            'name' => 'Kebero Música y Café',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        return $business;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createEvent(Business $business, array $overrides = []): PublicEvent
    {
        $title = $overrides['title'] ?? 'Evento de prueba';

        return PublicEvent::create(array_merge([
            'business_id' => $business->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.$business->id.'-'.random_int(1000, 9999),
            'starts_at' => now()->addDays(3),
            'status' => PublicEvent::PUBLICADO,
            'created_by_user_id' => $business->organization->owner->id,
        ], $overrides));
    }
}
