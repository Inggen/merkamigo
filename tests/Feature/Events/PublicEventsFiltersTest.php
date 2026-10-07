<?php

namespace Tests\Feature\Events;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Models\PublicEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 6: "filtros por
 * municipio, fecha y categoría"; "indexar solo eventos publicados y
 * futuros".
 */
class PublicEventsFiltersTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_the_category_filter_only_shows_matching_events(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $musica = $this->publishedEvent($business, $owner, 'Noche de música', 'musica');
        $taller = $this->publishedEvent($business, $owner, 'Taller de cerámica', 'taller');

        $response = $this->get(route('eventos', ['categoria' => 'musica']));

        $response->assertOk();
        $response->assertSee($musica->title);
        $response->assertDontSee($taller->title);
    }

    public function test_the_date_filter_only_shows_events_on_that_exact_date(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $day1 = $this->publishedEvent($business, $owner, 'Evento día 1', null, now()->addDays(2));
        $day2 = $this->publishedEvent($business, $owner, 'Evento día 2', null, now()->addDays(5));

        $response = $this->get(route('eventos', ['fecha' => now()->addDays(2)->toDateString()]));

        $response->assertOk();
        $response->assertSee($day1->title);
        $response->assertDontSee($day2->title);
    }

    public function test_a_draft_event_never_appears_in_the_public_listing(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        app(ManagePublicEvent::class)->create($business, [
            'title' => 'Todavía en borrador',
            'starts_at' => now()->addWeek(),
        ], $owner);

        $this->get(route('eventos'))->assertOk()->assertDontSee('Todavía en borrador');
    }

    public function test_a_past_event_never_appears_in_the_public_listing(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();
        $event = $this->publishedEvent($business, $owner, 'Evento pasado', null);
        // Publicado mientras era futuro (igual que en la vida real); el
        // tiempo simplemente pasó después.
        $event->update(['starts_at' => now()->subWeek()]);

        $this->get(route('eventos'))->assertOk()->assertDontSee('Evento pasado');
    }

    private function publishedEvent(
        Business $business,
        User $owner,
        string $title,
        ?string $category,
        mixed $startsAt = null,
    ): PublicEvent {
        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => $title,
            'category' => $category,
            'starts_at' => $startsAt ?? now()->addWeek(),
        ], $owner);

        return app(ManagePublicEvent::class)->publish($event);
    }
}
