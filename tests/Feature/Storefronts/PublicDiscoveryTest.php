<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.3 y 1.5 del TODO: vitrina pública y plaza del municipio.
 */
class PublicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(Municipality $municipality, Category $category): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pública',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ])->business;

        $business->update(['logo_path' => 'businesses/1/logo.jpg']);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_a_draft_business_does_not_appear_in_the_plaza_or_the_public_page(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create();
        $draft = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Borrador',
            'municipality_id' => $municipality->id,
        ])->business;

        $this->get(route('plaza.show', $municipality))
            ->assertOk()
            ->assertDontSee('Negocio Borrador');

        $this->get(route('vitrinas.show', $draft))->assertNotFound();
    }

    public function test_a_published_business_appears_in_its_municipality_plaza_and_public_page(): void
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        $otherMunicipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $this->get(route('plaza.show', $municipality))
            ->assertOk()
            ->assertSee($business->name);

        $this->get(route('plaza.show', $otherMunicipality))
            ->assertOk()
            ->assertDontSee($business->name);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee('Pan francés');
    }

    public function test_the_qr_endpoint_returns_a_png_image_for_a_published_business(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $response = $this->get(route('vitrinas.qr', $business));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_search_only_returns_published_businesses_matching_the_name(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $this->get(route('buscar', ['q' => 'Panadería']))
            ->assertOk()
            ->assertSee($business->name);

        $this->get(route('buscar', ['q' => 'Ferretería']))
            ->assertOk()
            ->assertDontSee($business->name);
    }
}
