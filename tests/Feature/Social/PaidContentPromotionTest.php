<?php

namespace Tests\Feature\Social;

use App\Domain\Analytics\Actions\CalculatePromotionPerformance;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Billing\Actions\ApplyBillingProductPurchase;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Payment;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Social\Actions\CreateContentPromotion;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaidContentPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_prepare_a_segmented_content_promotion_with_existing_prices(): void
    {
        [$owner, $business, $product] = $this->businessWithProduct();
        $package = $this->featuredPackage();

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->set('promotionTarget', 'product:'.$product->id)
            ->set('promotionRadiusKm', 5)
            ->call('promoteContent', $package->id)
            ->assertRedirect();

        $this->assertDatabaseHas('content_promotions', [
            'business_id' => $business->id,
            'promotable_type' => $product->getMorphClass(),
            'promotable_id' => $product->id,
            'municipality_id' => $business->municipality_id,
            'category_id' => $business->category_id,
            'radius_km' => 5,
            'status' => ContentPromotion::BORRADOR,
        ]);
    }

    public function test_approved_featured_payment_activates_content_instead_of_the_whole_storefront(): void
    {
        [$owner, $business, $product] = $this->businessWithProduct();
        $package = $this->featuredPackage();
        $promotion = app(CreateContentPromotion::class)->handle($business, $product, [], $owner);
        $payment = Payment::create([
            'business_id' => $business->id,
            'billing_product_id' => $package->id,
            'content_promotion_id' => $promotion->id,
            'reference' => 'MKA-PROMOTION-TEST',
            'amount_cents' => $package->price_cents,
            'currency' => 'COP',
            'status' => Payment::APROBADO,
        ]);

        app(ApplyBillingProductPurchase::class)->handle($payment);

        $this->assertTrue($promotion->fresh()->isActive());
        $this->assertNull($business->fresh()->featured_until);
    }

    public function test_promoted_post_is_prioritized_and_impressions_clicks_and_sales_are_attributed(): void
    {
        [$owner, $business, $product] = $this->businessWithProduct();
        $post = app(CreatePost::class)->handle($business, [
            'type' => 'texto',
            'body' => 'Contenido patrocinado',
            'product_ids' => [$product->id],
        ], [], $owner);
        $promotion = app(CreateContentPromotion::class)->handle($business, $post, [], $owner);
        $promotion->update([
            'status' => ContentPromotion::ACTIVA,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('Patrocinado'))
            ->assertSee('Contenido patrocinado');

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get(route('promotions.click', $promotion))
            ->assertRedirect(route('vitrinas.show', $business));

        $business->wompiCredential()->create([
            'public_key' => 'pub_test_promotion',
            'private_key' => 'prv_test_promotion',
            'integrity_secret' => 'integrity-promotion',
            'events_secret' => 'events-promotion',
            'environment' => 'sandbox',
            'is_active' => true,
            'connected_at' => now(),
        ]);
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer, $promotion);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'promotion-sale');

        $metrics = app(CalculatePromotionPerformance::class)->handle($business, 7);

        $this->assertSame(1, $metrics['impressions']);
        $this->assertSame(1, $metrics['clicks']);
        $this->assertSame(1, $metrics['conversions']);
        $this->assertDatabaseHas('analytics_events', [
            'type' => AnalyticsEvent::PROMOTION_CONVERSION,
            'subject_id' => $promotion->id,
        ]);
    }

    private function businessWithProduct(): array
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá',
            'slug' => 'cajica-promotion',
            'department' => 'Cundinamarca',
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos-promotion', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Promocionado',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);
        $product = $business->products()->create([
            'name' => 'Producto local',
            'slug' => 'producto-local-promotion',
            'type' => 'producto',
            'price' => 25000,
            'price_type' => 'exacto',
            'status' => 'publicado',
            'is_available' => true,
        ]);

        return [$owner, $business->fresh(), $product];
    }

    private function featuredPackage(): BillingProduct
    {
        return BillingProduct::create([
            'slug' => 'destacado-social-7',
            'name' => 'Destacado 7 días',
            'description' => 'Promoción durante 7 días.',
            'price_cents' => 990000,
            'kind' => BillingProduct::DESTACADO,
            'payload' => ['days' => 7],
            'is_active' => true,
        ]);
    }
}
