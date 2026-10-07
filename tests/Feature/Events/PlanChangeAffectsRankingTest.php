<?php

namespace Tests\Feature\Events;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Events\Actions\ManagePublicEvent;
use App\Domain\Events\Actions\RankPublicEventsForAgenda;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\Events\Concerns\SetsUpEventsBusiness;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 7: "Pruebas de
 * integración de... cambios de plan." El impulso de visibilidad
 * (Fase 6) debe reflejar el plan VIGENTE al momento de ordenar la
 * agenda, no uno que haya quedado en caché de cuando se creó el evento.
 */
class PlanChangeAffectsRankingTest extends TestCase
{
    use RefreshDatabase, SetsUpEventsBusiness;

    public function test_upgrading_a_businesss_plan_improves_its_ranking_on_the_next_request_without_touching_the_event(): void
    {
        [$basicBusiness, $basicOwner] = $this->eventsReadyBusiness();
        [$upgradingBusiness, $upgradingOwner] = $this->eventsReadyBusiness();

        $plan = Plan::firstOrCreate(
            ['slug' => 'negocios'],
            ['name' => 'Negocios', 'price_cents' => 9900000, 'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 2],
        );

        $sameDay = now()->addDay()->setTime(10, 0);

        $basicEvent = app(ManagePublicEvent::class)->create($basicBusiness, ['title' => 'Básico', 'starts_at' => $sameDay], $basicOwner);
        $basicEvent = app(ManagePublicEvent::class)->publish($basicEvent);

        $otherEvent = app(ManagePublicEvent::class)->create($upgradingBusiness, ['title' => 'Otro negocio, todavía gratis', 'starts_at' => $sameDay], $upgradingOwner);
        $otherEvent = app(ManagePublicEvent::class)->publish($otherEvent);

        // Antes de actualizar el plan, ambos pesan igual (ninguno paga
        // todavía) — el orden entre ellos no está garantizado, solo que
        // están ambos presentes.
        $before = app(RankPublicEventsForAgenda::class)->handle($this->reload([$basicEvent, $otherEvent]));
        $this->assertCount(2, $before);

        // El negocio de "otherEvent" sube a Negocios DESPUÉS de crear y
        // publicar su evento — el evento en sí no se vuelve a tocar.
        app(SubscribeToPlan::class)->handle($otherEvent->business, $plan, $upgradingOwner);

        $after = app(RankPublicEventsForAgenda::class)->handle($this->reload([$basicEvent, $otherEvent]));

        $this->assertSame($otherEvent->id, $after[0]->id);
        $this->assertSame($basicEvent->id, $after[1]->id);
    }

    /**
     * @param  array<int, PublicEvent>  $events
     */
    private function reload(array $events): Collection
    {
        return PublicEvent::query()
            ->with('business.subscription.plan')
            ->whereIn('id', array_map(fn (PublicEvent $e) => $e->id, $events))
            ->get();
    }
}
