<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.8 del TODO: métricas comprensibles. Cubre el registro de eventos desde
 * las páginas públicas (visita, clic a WhatsApp, QR, compartir), la
 * deduplicación, el filtro de bots y el aislamiento entre negocios.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $suffix = uniqid();
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Medida '.$suffix,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_visiting_the_vitrina_registers_a_view(): void
    {
        $business = $this->publishedBusiness();

        $this->get(route('vitrinas.show', $business))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => AnalyticsEvent::VITRINA_VIEW,
            'subject_type' => null,
        ]);
    }

    public function test_visiting_a_product_registers_a_product_view(): void
    {
        $business = $this->publishedBusiness();
        $product = $business->products()->first();
        $product->update(['status' => 'publicado']);

        $this->get(route('vitrinas.product', [$business, $product]))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => AnalyticsEvent::PRODUCTO_VIEW,
            'subject_type' => (new Product)->getMorphClass(),
            'subject_id' => $product->id,
        ]);
    }

    public function test_repeated_visits_from_the_same_visitor_do_not_duplicate_the_event(): void
    {
        $business = $this->publishedBusiness();

        $this->get(route('vitrinas.show', $business))->assertOk();
        $this->get(route('vitrinas.show', $business))->assertOk();
        $this->get(route('vitrinas.show', $business))->assertOk();

        $this->assertSame(1, AnalyticsEvent::where('business_id', $business->id)
            ->where('type', AnalyticsEvent::VITRINA_VIEW)
            ->count());
    }

    public function test_a_known_bot_user_agent_does_not_register_a_view(): void
    {
        $business = $this->publishedBusiness();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->get(route('vitrinas.show', $business))
            ->assertOk();

        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function test_clicking_whatsapp_registers_a_click_and_redirects_to_wa_me(): void
    {
        $business = $this->publishedBusiness();

        $response = $this->get(route('vitrinas.whatsapp', $business));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/573001112233', $response->headers->get('Location'));

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => AnalyticsEvent::WHATSAPP_CLICK,
            'subject_type' => null,
        ]);
    }

    public function test_fetching_the_qr_registers_a_qr_view(): void
    {
        $business = $this->publishedBusiness();

        $this->get(route('vitrinas.qr', $business))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => AnalyticsEvent::QR_VIEW,
        ]);
    }

    public function test_the_compartir_beacon_registers_a_click_without_a_csrf_token(): void
    {
        $business = $this->publishedBusiness();

        $this->post(route('vitrinas.compartir', $business))->assertNoContent();

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => AnalyticsEvent::COMPARTIR_CLICK,
        ]);
    }

    public function test_metrics_are_scoped_to_their_own_business(): void
    {
        $businessA = $this->publishedBusiness();
        $businessB = $this->publishedBusiness();

        $this->get(route('vitrinas.show', $businessA));
        $this->get(route('vitrinas.show', $businessA));

        $this->assertSame(1, AnalyticsEvent::where('business_id', $businessA->id)->count());
        $this->assertSame(0, AnalyticsEvent::where('business_id', $businessB->id)->count());
    }
}
