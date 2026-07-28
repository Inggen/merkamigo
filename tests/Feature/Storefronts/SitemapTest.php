<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.1 del TODO: SEO técnico básico (sitemap.xml).
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sitemap_includes_static_pages_and_published_businesses_only(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pública',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2000,
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        $draftOwner = User::factory()->create();
        app(CreateStorefront::class)->handle($draftOwner, ['name' => 'Negocio Borrador']);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('vitrinas.show', $business->fresh()), false);
        $response->assertSee(route('home'), false);
        $response->assertSee(route('plaza.show', $municipality), false);
        $response->assertDontSee('negocio-borrador');
    }
}
