<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Exceptions\PlanLimitException;
use App\Domain\Billing\Models\Plan;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plan "Negocios" (2026-09-30): tercer nivel por encima de Emprendedor,
 * que a su vez dejó de tener productos ilimitados (ahora tope real de 20).
 * `Business::isOnPaidPlan()` reemplazó la comparación literal del slug
 * 'emprendedor' precisamente para que Negocios herede los mismos perks
 * sin repetir la condición en cada sitio que la usaba. El asistente IA y
 * las métricas avanzadas, en cambio, son exclusivos de Negocios —
 * `Business::isOnTopPlan()`, no `isOnPaidPlan()`.
 */
class NegociosPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_negocios_plan_has_more_capacity_than_emprendedor_in_every_limit(): void
    {
        $this->seed(PlanSeeder::class);

        $emprendedor = Plan::where('slug', 'emprendedor')->firstOrFail();
        $negocios = Plan::where('slug', 'negocios')->firstOrFail();

        $this->assertGreaterThan($emprendedor->limit('max_products'), $negocios->limit('max_products'));
        $this->assertGreaterThan($emprendedor->limit('max_storefronts'), $negocios->limit('max_storefronts'));
        $this->assertGreaterThan($emprendedor->limit('max_members'), $negocios->limit('max_members'));
        $this->assertGreaterThan($emprendedor->limit('max_featured_days'), $negocios->limit('max_featured_days'));
    }

    public function test_a_business_on_the_negocios_plan_can_use_the_ai_assistant_perks(): void
    {
        $this->seed(PlanSeeder::class);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Grande'])->business;

        app(SubscribeToPlan::class)->handle($business, Plan::where('slug', 'negocios')->firstOrFail(), $owner);

        $this->assertTrue($business->fresh()->isOnPaidPlan());
        $this->assertTrue($business->fresh()->canUseAiChatbot());
    }

    public function test_creating_more_than_twenty_products_on_the_emprendedor_plan_is_rejected(): void
    {
        $this->seed(PlanSeeder::class);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Emprendedor'])->business;

        app(SubscribeToPlan::class)->handle($business, Plan::where('slug', 'emprendedor')->firstOrFail(), $owner);

        for ($i = 0; $i < 20; $i++) {
            app(CreateProduct::class)->handle($business->fresh(), [
                'name' => "Producto {$i}", 'type' => 'producto', 'price_type' => 'consultar',
            ], [], $owner);
        }

        $this->expectException(PlanLimitException::class);

        app(CreateProduct::class)->handle($business->fresh(), [
            'name' => 'Producto 21', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
    }
}
