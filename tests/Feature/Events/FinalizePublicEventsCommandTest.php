<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

class FinalizePublicEventsCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_a_published_event_past_its_end_time_is_finalized(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, ['title' => 'Evento', 'starts_at' => now()->addDay()], $owner);
        $event = app(ManagePublicEvent::class)->publish($event);
        $event->update(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHour()]);

        $this->artisan('events:finalize-public-events')->assertSuccessful();

        $this->assertSame(PublicEvent::FINALIZADO, $event->fresh()->status);
    }

    public function test_a_published_event_without_an_end_time_finalizes_once_its_start_time_passes(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, ['title' => 'Evento', 'starts_at' => now()->addDay()], $owner);
        $event = app(ManagePublicEvent::class)->publish($event);
        $event->update(['starts_at' => now()->subHour()]);

        $this->artisan('events:finalize-public-events')->assertSuccessful();

        $this->assertSame(PublicEvent::FINALIZADO, $event->fresh()->status);
    }

    public function test_a_future_published_event_is_left_untouched(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, ['title' => 'Evento', 'starts_at' => now()->addDay()], $owner);
        $event = app(ManagePublicEvent::class)->publish($event);

        $this->artisan('events:finalize-public-events')->assertSuccessful();

        $this->assertSame(PublicEvent::PUBLICADO, $event->fresh()->status);
    }

    public function test_a_cancelled_event_is_not_affected(): void
    {
        [$business, $owner] = $this->eventsReadyBusiness();

        $event = app(ManagePublicEvent::class)->create($business, ['title' => 'Evento', 'starts_at' => now()->addDay()], $owner);
        $event = app(ManagePublicEvent::class)->publish($event);
        app(ManagePublicEvent::class)->cancel($event);
        $event->update(['starts_at' => now()->subDay()]);

        $this->artisan('events:finalize-public-events')->assertSuccessful();

        $this->assertSame(PublicEvent::CANCELADO, $event->fresh()->status);
    }
}
