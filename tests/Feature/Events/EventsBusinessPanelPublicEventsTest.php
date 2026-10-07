<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Models\PublicEvent;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * Panel de negocio: CRUD de eventos públicos y "Publicar en el feed"
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5).
 */
class EventsBusinessPanelPublicEventsTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_the_owner_can_create_publish_and_cancel_a_public_event(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Noche de música y café')
            ->set('eventCategory', 'musica')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent')
            ->assertHasNoErrors();

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(PublicEvent::BORRADOR, $event->status);
        $this->assertSame('musica', $event->category);

        $component->call('publishPublicEvent', $event->id)->assertHasNoErrors();
        $this->assertSame(PublicEvent::PUBLICADO, $event->fresh()->status);

        $component->call('cancelPublicEvent', $event->id)->assertHasNoErrors();
        $this->assertSame(PublicEvent::CANCELADO, $event->fresh()->status);
    }

    public function test_the_owner_can_upload_a_cover_image(): void
    {
        Storage::fake('public');
        [$business, $owner] = $this->eventsReadyBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Mercado artesanal')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('eventCover', UploadedFile::fake()->image('portada.jpg', 1200, 800))
            ->call('savePublicEvent')
            ->assertHasNoErrors();

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $this->assertNotNull($event->cover_path);
        Storage::disk('public')->assertExists($event->cover_path);

        $component
            ->call('editPublicEvent', $event->id)
            ->assertSet('currentEventCoverUrl', $event->coverUrl());
    }

    public function test_blocking_a_space_without_selecting_one_is_rejected(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Evento con espacio')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('eventBlocksSpace', true)
            ->call('savePublicEvent');

        $this->assertSame(0, PublicEvent::where('business_id', $business->id)->count());
    }

    public function test_the_owner_can_publish_a_published_event_to_the_feed_once(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Noche de música y café')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent');

        $event = PublicEvent::where('business_id', $business->id)->firstOrFail();
        $component->call('publishPublicEvent', $event->id);

        $component->call('publishToFeed', $event->id)->assertHasNoErrors();

        $this->assertSame(1, Post::where('public_event_id', $event->id)->count());
    }

    public function test_a_collaborator_cannot_create_public_events(): void
    {
        [$business] = $this->eventsReadyBusiness();
        $collaborator = User::factory()->create();
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($business->id);
        $collaborator->assignRole(Role::findOrCreate('collaborator', 'web'));
        setPermissionsTeamId($previousTeamId);

        Livewire::actingAs($collaborator)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Intento no autorizado')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent');

        $this->assertSame(0, PublicEvent::where('business_id', $business->id)->count());
    }

    public function test_the_vitrina_shows_upcoming_published_events_but_not_drafts(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Publicado y visible')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent');
        $published = PublicEvent::where('title', 'Publicado y visible')->firstOrFail();
        $component->call('publishPublicEvent', $published->id);

        $component
            ->call('startNewPublicEvent')
            ->set('eventTitle', 'Todavía en borrador')
            ->set('eventStartsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('savePublicEvent');

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Publicado y visible')
            ->assertDontSee('Todavía en borrador');
    }
}
