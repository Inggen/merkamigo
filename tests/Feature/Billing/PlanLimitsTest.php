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
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 4.1 del TODO: planes y límites.
 */
class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function emprendedorPlan(): Plan
    {
        return Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Más productos, colaboradores y destacados.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7],
            'trial_days' => 14,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    public function test_a_new_business_defaults_to_the_free_plan(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Nuevo'])->business;

        $this->assertSame('gratis', $business->activePlan()->slug);
    }

    public function test_creating_a_product_beyond_the_plan_limit_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Al Límite'])->business;

        for ($i = 0; $i < 10; $i++) {
            app(CreateProduct::class)->handle($business, [
                'name' => "Producto {$i}", 'type' => 'producto', 'price_type' => 'consultar',
            ], [], $owner);
        }

        $this->expectException(PlanLimitException::class);

        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto extra', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
    }

    public function test_upgrading_to_a_paid_plan_lifts_the_product_limit(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Emprendedor'])->business;

        $plan = $this->emprendedorPlan();
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        for ($i = 0; $i < 11; $i++) {
            app(CreateProduct::class)->handle($business->fresh(), [
                'name' => "Producto {$i}", 'type' => 'producto', 'price_type' => 'consultar',
            ], [], $owner);
        }

        $this->assertSame(11, $business->fresh()->products()->count());
    }

    public function test_owner_can_switch_to_a_free_plan_from_the_plan_page(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Plan'])->business;

        $plan = $this->emprendedorPlan();
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.plan', ['business' => $business->id])
            ->assertSee('Emprendedor');

        $freePlan = Plan::where('slug', 'gratis')->firstOrFail();

        Livewire::test('pages::emprendedores.negocios.plan', ['business' => $business->id])
            ->call('switchToFreePlan', $freePlan->id)
            ->assertSee('Gratis');

        $this->assertSame('gratis', $business->fresh()->activePlan()->slug);
    }

    public function test_the_plan_page_shows_usage_features_and_the_plan_comparison(): void
    {
        $this->seed(PlanSeeder::class);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Plan Visual'])->business;

        $plan = Plan::where('slug', 'emprendedor')->firstOrFail();
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.plan', ['business' => $business->id])
            ->assertSee('Plan actual')
            ->assertSee('$19.900 COP')
            ->assertSee('Activo')
            ->assertSee('Productos y servicios ilimitados')
            ->assertSee('Uso de tu plan')
            ->assertSee('de 5')
            ->assertSee('4 cupos disponibles')
            ->assertSee('Gestionar equipo')
            ->assertSee('Comparar planes')
            ->assertSee('Plan básico')
            ->assertSee('Tu plan actual')
            ->assertSee('Cambiar a Gratis')
            ->assertSee('Contáctanos');
    }

    public function test_creating_more_storefronts_than_the_free_plan_allows_is_rejected(): void
    {
        $owner = User::factory()->create();
        app(CreateStorefront::class)->handle($owner, ['name' => 'Primera Vitrina']);

        $this->expectException(PlanLimitException::class);

        app(CreateStorefront::class)->handle($owner, ['name' => 'Segunda Vitrina']);
    }

    public function test_upgrading_one_storefront_lifts_the_storefront_limit_for_new_ones(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Primera Vitrina'])->business;

        $plan = $this->emprendedorPlan();
        $plan->update(['limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7, 'max_storefronts' => 3]]);
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        $second = app(CreateStorefront::class)->handle($owner, ['name' => 'Segunda Vitrina']);
        $third = app(CreateStorefront::class)->handle($owner, ['name' => 'Tercera Vitrina']);

        $this->assertNotNull($second->business);
        $this->assertNotNull($third->business);

        $this->expectException(PlanLimitException::class);

        app(CreateStorefront::class)->handle($owner, ['name' => 'Cuarta Vitrina']);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_plan_page(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::emprendedores.negocios.plan', ['business' => $businessA->id])
            ->assertForbidden();
    }
}
