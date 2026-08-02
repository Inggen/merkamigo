<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Actions\CalculateConversionFunnel;
use App\Domain\Analytics\Actions\CalculateProductPerformance;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 4.5 del TODO: embudo de conversión, desglose por producto y exportación
 * de datos propios.
 */
class GrowthAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_conversion_funnel_counts_visits_clicks_and_completed_orders_in_the_last_seven_days(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Embudo'])->business;

        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'b']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::WHATSAPP_CLICK, 'visitor_hash' => 'a']);

        $customer = User::factory()->create();
        OrderConfirmation::create([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'created_by' => $owner->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'status' => OrderConfirmation::COMPLETADO,
            'completed_at' => now(),
        ]);

        // Fuera del periodo de 7 días — no debe contar.
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'c'])
            ->forceFill(['created_at' => now()->subDays(30)])
            ->save();

        $funnel = app(CalculateConversionFunnel::class)->handle($business);

        $this->assertSame(2, $funnel['visits']);
        $this->assertSame(1, $funnel['whatsapp_clicks']);
        $this->assertSame(1, $funnel['completed_orders']);
        $this->assertSame(50.0, $funnel['visit_to_click_rate']);
        $this->assertSame(100.0, $funnel['click_to_order_rate']);
    }

    public function test_product_performance_ranks_products_by_views_and_ignores_other_businesses(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Productos'])->business;

        $popular = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto popular', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $popular->update(['status' => 'publicado']);

        $quiet = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto silencioso', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $quiet->update(['status' => 'publicado']);

        $otherOwner = User::factory()->create();
        $otherBusiness = app(CreateStorefront::class)->handle($otherOwner, ['name' => 'Otro Negocio'])->business;
        $otherProduct = app(CreateProduct::class)->handle($otherBusiness, [
            'name' => 'Producto ajeno', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $otherOwner);
        $otherProduct->update(['status' => 'publicado']);

        foreach (['a', 'b', 'c'] as $hash) {
            AnalyticsEvent::create([
                'business_id' => $business->id, 'type' => AnalyticsEvent::PRODUCTO_VIEW,
                'subject_type' => $popular->getMorphClass(), 'subject_id' => $popular->id, 'visitor_hash' => $hash,
            ]);
        }
        AnalyticsEvent::create([
            'business_id' => $business->id, 'type' => AnalyticsEvent::WHATSAPP_CLICK,
            'subject_type' => $popular->getMorphClass(), 'subject_id' => $popular->id, 'visitor_hash' => 'a',
        ]);
        AnalyticsEvent::create([
            'business_id' => $otherBusiness->id, 'type' => AnalyticsEvent::PRODUCTO_VIEW,
            'subject_type' => $otherProduct->getMorphClass(), 'subject_id' => $otherProduct->id, 'visitor_hash' => 'z',
        ]);

        $rows = app(CalculateProductPerformance::class)->handle($business);

        $this->assertCount(2, $rows);
        $this->assertSame('Producto popular', $rows[0]['product']->name);
        $this->assertSame(3, $rows[0]['views']);
        $this->assertSame(1, $rows[0]['whatsapp_clicks']);
        $this->assertNotNull($rows[0]['last_viewed_at']);
        $this->assertSame('Producto silencioso', $rows[1]['product']->name);
        $this->assertSame(0, $rows[1]['views']);
        $this->assertNull($rows[1]['last_viewed_at']);
    }

    public function test_the_metrics_page_shows_the_funnel_and_product_performance(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Panel'])->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto Visible', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        AnalyticsEvent::create([
            'business_id' => $business->id, 'type' => AnalyticsEvent::PRODUCTO_VIEW,
            'subject_type' => $product->getMorphClass(), 'subject_id' => $product->id, 'visitor_hash' => 'a',
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.metricas', ['business' => $business->id])
            ->assertOk()
            ->assertSee(__('Tu embudo de conversión esta semana'))
            ->assertSee('Producto Visible')
            ->assertSee(__('Exportar mis datos (CSV)'));
    }

    public function test_owner_can_export_their_own_data_as_csv(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Exporta'])->business;

        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);

        $response = $this->actingAs($owner)
            ->get(route('emprendedores.negocios.metricas.exportar', $business));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('tipo,fecha,detalle', $content);
        $this->assertStringContainsString('evento', $content);
    }

    public function test_a_collaborator_of_another_business_cannot_export_its_data(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $this->actingAs(User::factory()->create())
            ->get(route('emprendedores.negocios.metricas.exportar', $businessA))
            ->assertForbidden();
    }
}
