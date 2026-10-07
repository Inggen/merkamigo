<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Models\PublicEvent;
use App\Domain\Social\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 7 — prueba end-to-end
 * literal del documento: "crear evento público → publicar en
 * vitrina/feed/agenda."
 */
class PublicEventEndToEndTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_a_public_event_created_in_the_panel_ends_up_visible_in_the_storefront_feed_and_agenda(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        // 1) El dueño lo crea como borrador y lo publica desde el panel.
        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Noche de música y café')
            ->set('eventCategory', 'musica')
            ->set('eventDescription', 'Una velada íntima con talentos locales.')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent')
            ->assertHasNoErrors();

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(PublicEvent::BORRADOR, $event->status);

        $component->call('publishPublicEvent', $event->id)->assertHasNoErrors();
        $this->assertSame(PublicEvent::PUBLICADO, $event->fresh()->status);

        // 2) Visible en la vitrina.
        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Noche de música y café');

        // 3) Visible en la agenda pública general.
        $this->get(route('eventos'))
            ->assertOk()
            ->assertSee('Noche de música y café');

        // 4) Visible en la ficha propia del evento.
        $this->get(route('eventos.show', $event))
            ->assertOk()
            ->assertSee('Noche de música y café')
            ->assertSee('Una velada íntima con talentos locales.');

        // 5) "Publicar en el feed" — un solo post, con enlace de vuelta a la ficha.
        $component->call('publishToFeed', $event->id)->assertHasNoErrors();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Noche de música y café')
            ->assertSee('data-event-post-card', false)
            ->assertSee('Ver evento')
            ->assertSee(route('eventos.show', $event), false);

        // Repetir "Publicar en el feed" no debe crear un segundo post.
        $component->call('publishToFeed', $event->id);
        $this->assertSame(1, Post::where('public_event_id', $event->id)->count());
    }

    /**
     * Pedido del usuario (2026-10-06): "agrega otra pestaña más que se
     * llame eventos y muestra allí los eventos asociados a la vitrina."
     * Mismo criterio de "nunca una pestaña vacía" que ya usan
     * Recompensas/Contenido en esa misma página.
     */
    public function test_the_storefront_shows_an_events_tab_only_when_there_is_an_upcoming_published_event(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        // Sin eventos próximos todavía: ni la pestaña ni su contenido
        // deben aparecer (el nav del header ya trae un enlace "Eventos"
        // genérico, así que se verifica por el enlace a la ficha, que sí
        // es específico de esta prueba).
        $before = $this->get(route('vitrinas.show', $business))->assertOk();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Feria artesanal de fin de semana')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent')
            ->assertHasNoErrors();

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $before->assertDontSee(route('eventos.show', $event), false);

        $component->call('publishPublicEvent', $event->id)->assertHasNoErrors();

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Feria artesanal de fin de semana')
            ->assertSee(route('eventos.show', $event), false);
    }

    public function test_a_past_published_event_does_not_show_in_the_storefronts_events_tab(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Evento futuro para publicar')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent')
            ->assertHasNoErrors();

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $component->call('publishPublicEvent', $event->id)->assertHasNoErrors();

        // Ya pasó, aunque siga "publicado" — no debe volver a aparecer.
        $event->forceFill(['starts_at' => now()->subWeek()])->save();

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertDontSee('Evento futuro para publicar')
            ->assertDontSee(route('eventos.show', $event), false);
    }
}
