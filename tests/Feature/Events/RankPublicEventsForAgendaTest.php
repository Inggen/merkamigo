<?php

namespace Tests\Feature\Events;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Actions\RankPublicEventsForAgenda;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 6 — criterio de
 * aceptación: "Negocios recibe un impulso comprobable entre eventos
 * igual de relevantes; Básico no desaparece de la agenda; un evento de
 * otra zona no desplaza sistemáticamente a uno cercano por el plan."
 */
class RankPublicEventsForAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_within_the_same_day_negocios_ranks_above_emprendedor_and_basico(): void
    {
        $basico = $this->eventOnPlan('gratis', now()->addDay()->setTime(10, 0));
        $negocios = $this->eventOnPlan('negocios', now()->addDay()->setTime(10, 0));
        $emprendedor = $this->eventOnPlan('emprendedor', now()->addDay()->setTime(10, 0));

        $ranked = app(RankPublicEventsForAgenda::class)->handle(
            $this->loadForRanking([$basico, $negocios, $emprendedor]),
        );

        $this->assertSame($negocios->id, $ranked[0]->id);
        $this->assertSame($emprendedor->id, $ranked[1]->id);
        $this->assertSame($basico->id, $ranked[2]->id);
    }

    public function test_basico_is_never_excluded_from_the_ranked_results(): void
    {
        $basico = $this->eventOnPlan('gratis', now()->addDay());
        $negocios1 = $this->eventOnPlan('negocios', now()->addDay());
        $negocios2 = $this->eventOnPlan('negocios', now()->addDay());

        $ranked = app(RankPublicEventsForAgenda::class)->handle(
            $this->loadForRanking([$basico, $negocios1, $negocios2]),
        );

        $this->assertCount(3, $ranked);
        $this->assertTrue($ranked->contains('id', $basico->id));
    }

    public function test_an_event_on_a_later_day_never_outranks_one_happening_sooner_regardless_of_plan(): void
    {
        $soonBasico = $this->eventOnPlan('gratis', now()->addDay());
        $laterNegocios = $this->eventOnPlan('negocios', now()->addWeek());

        $ranked = app(RankPublicEventsForAgenda::class)->handle(
            $this->loadForRanking([$laterNegocios, $soonBasico]),
        );

        $this->assertSame($soonBasico->id, $ranked[0]->id);
        $this->assertSame($laterNegocios->id, $ranked[1]->id);
    }

    public function test_it_does_not_crash_on_an_empty_collection(): void
    {
        $ranked = app(RankPublicEventsForAgenda::class)->handle($this->loadForRanking([]));

        $this->assertCount(0, $ranked);
    }

    /**
     * @param  array<int, PublicEvent>  $events
     */
    private function loadForRanking(array $events): Collection
    {
        return PublicEvent::query()
            ->with('business.subscription.plan')
            ->whereIn('id', array_map(fn (PublicEvent $e) => $e->id, $events))
            ->get();
    }

    private function eventOnPlan(string $planSlug, CarbonInterface $startsAt): PublicEvent
    {
        static $counter = 0;
        $counter++;

        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => "Negocio Ranking {$counter}",
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        if ($planSlug !== 'gratis') {
            $plan = Plan::firstOrCreate(
                ['slug' => $planSlug],
                ['name' => ucfirst($planSlug), 'price_cents' => 9900000, 'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 1],
            );
            app(SubscribeToPlan::class)->handle($business, $plan, $owner);
        }

        return PublicEvent::create([
            'business_id' => $business->id,
            'title' => "Evento {$counter}",
            'slug' => "evento-ranking-{$counter}",
            'starts_at' => $startsAt,
            'status' => PublicEvent::PUBLICADO,
            'created_by_user_id' => $owner->id,
        ]);
    }
}
