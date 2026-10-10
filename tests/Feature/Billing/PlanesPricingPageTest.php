<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Plan;
use Database\Seeders\BillingProductSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Página pública "Planes y precios": debe reflejar siempre los datos
 * reales de `Plan`/`BillingProduct` (editables desde Filament), nunca un
 * precio o límite escrito a mano en la vista.
 */
class PlanesPricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_the_three_active_plans_with_their_real_prices(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertSee('Básico')
            ->assertSee('Emprendedor')
            ->assertSee('Negocios')
            ->assertSee('$0 COP')
            ->assertSee('$49.900 COP')
            ->assertSee('$99.000 COP')
            // Ninguno de los 3 planes tiene productos ilimitados hoy — 50
            // (Negocios) es el tope más alto.
            ->assertSee('50')
            ->assertSee('20');
    }

    /**
     * PR4 de TODO_VENTAS_RENTABILIDAD.md (decisión del usuario
     * 2026-10-09): el checkout real cobra de inmediato, nunca honra
     * `trial_days` en este autoservicio — la página ya no debe
     * prometer un trial que no existe.
     */
    public function test_the_pricing_page_no_longer_promises_a_free_trial(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertDontSee('días de prueba')
            ->assertDontSee('día de prueba');
    }

    public function test_the_comparison_table_shows_which_plan_includes_each_qualitative_feature(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(BillingProductSeeder::class);

        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertSee('Copiloto de WhatsApp')
            ->assertSee('En vivo (Live Commerce)')
            ->assertSee('Asistente IA (vitrina, descripciones, fotos, chatbot)')
            ->assertSee('Métricas avanzadas (90 días + exportar CSV)')
            ->assertSee('Ya incluido en el plan Negocios.');
    }

    public function test_an_inactive_plan_is_not_shown(): void
    {
        $this->seed(PlanSeeder::class);
        Plan::where('slug', 'emprendedor')->update(['is_active' => false]);

        // No se busca la palabra "Emprendedor": también nombra la
        // experiencia de usuario en la navegación compartida, presente sin
        // importar el estado del plan. Se busca su descripción, propia de
        // la tarjeta de ese plan.
        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertSee('Básico')
            ->assertDontSee('Más productos, colaboradores y destacados');
    }

    public function test_active_billing_products_are_shown_with_their_real_prices(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(BillingProductSeeder::class);

        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertSee('Kit Arranca Bonito')
            ->assertSee('$99.900 COP')
            ->assertSee('$9.900 COP');
    }

    public function test_an_inactive_billing_product_is_not_shown(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(BillingProductSeeder::class);
        BillingProduct::where('slug', 'kit-arranca-bonito')->update(['is_active' => false]);

        $this->get(route('planes-y-precios'))
            ->assertOk()
            ->assertDontSee('Kit Arranca Bonito');
    }
}
