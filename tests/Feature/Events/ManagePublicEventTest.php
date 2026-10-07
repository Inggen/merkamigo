<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Actions\PublishPublicEventToFeed;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Social\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5 — criterio de
 * aceptación: "un evento abierto de Kebero se ve en su vitrina y un solo
 * post del feed lleva a la misma ficha; una reserva privada no se vuelve
 * pública."
 */
class ManagePublicEventTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_a_draft_event_can_be_created_and_published(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Noche de música y café',
            'starts_at' => now()->addWeek(),
        ], $owner);

        $this->assertSame(PublicEvent::BORRADOR, $event->status);
        $this->assertNotEmpty($event->slug);

        $published = app(ManagePublicEvent::class)->publish($event);

        $this->assertSame(PublicEvent::PUBLICADO, $published->status);
    }

    public function test_an_event_cannot_be_published_if_its_date_already_passed(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Evento vencido',
            'starts_at' => now()->subDay(),
        ], $owner);

        $this->expectException(EventActionException::class);

        app(ManagePublicEvent::class)->publish($event);
    }

    public function test_blocking_a_space_requires_selecting_a_valid_space_of_the_same_business(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();
        [$otherBusiness] = $this->eventsReadyBusiness();
        $otherSpace = $this->eventsSpace($otherBusiness);

        $this->expectException(EventActionException::class);

        app(ManagePublicEvent::class)->create($business, [
            'title' => 'Mercado artesanal',
            'starts_at' => now()->addWeek(),
            'blocks_space' => true,
            'event_space_id' => $otherSpace->id,
        ], $owner);
    }

    public function test_a_cancelled_event_no_longer_accepts_changes(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Evento a cancelar',
            'starts_at' => now()->addWeek(),
        ], $owner);
        app(ManagePublicEvent::class)->publish($event);
        app(ManagePublicEvent::class)->cancel($event);

        $this->expectException(EventActionException::class);

        app(ManagePublicEvent::class)->update($event, ['title' => 'Nuevo título']);
    }

    public function test_publishing_an_event_that_blocks_a_space_prevents_an_overlapping_private_reservation(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $start = now()->addDay()->setTime(14, 0);

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Mercado artesanal',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(3),
            'blocks_space' => true,
            'event_space_id' => $space->id,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event);

        $this->expectException(EventActionException::class);

        app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy()->addHour(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );
    }

    public function test_publishing_an_informational_event_that_does_not_block_a_space_does_not_affect_reservations(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness('horas', 8000000);
        $space = $this->eventsSpace($business);
        $start = now()->addDay()->setTime(14, 0);

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Anuncio informativo',
            'starts_at' => $start,
        ], $owner);
        app(ManagePublicEvent::class)->publish($event);

        $reservation = app(CreateEventReservation::class)->handle(
            $business, $space, $start->copy(), 1, 2, [], [],
            'Juan', 'juan@example.com', '+573001112233', null, (string) Str::uuid(),
        );

        $this->assertNotNull($reservation->id);
    }

    public function test_publishing_to_the_feed_creates_exactly_one_linked_post(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Noche de música y café',
            'description' => 'Una velada íntima.',
            'starts_at' => now()->addWeek(),
        ], $owner);
        app(ManagePublicEvent::class)->publish($event);

        $post = app(PublishPublicEventToFeed::class)->handle($event, $owner);

        $this->assertSame($event->id, $post->public_event_id);
        $this->assertSame(1, Post::where('public_event_id', $event->id)->count());

        $this->expectException(EventActionException::class);
        app(PublishPublicEventToFeed::class)->handle($event->fresh(), $owner);
    }

    public function test_a_draft_event_cannot_be_published_to_the_feed(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, [
            'title' => 'Todavía en borrador',
            'starts_at' => now()->addWeek(),
        ], $owner);

        $this->expectException(EventActionException::class);

        app(PublishPublicEventToFeed::class)->handle($event, $owner);
    }
}
