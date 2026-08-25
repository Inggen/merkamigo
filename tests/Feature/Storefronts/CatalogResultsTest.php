<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Livewire\CatalogResults;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_pages_update_inside_the_livewire_component(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $older = $this->publishedBusiness($municipality, $category, 'Vitrina anterior', 'Producto anterior');
        $newer = $this->publishedBusiness($municipality, $category, 'Vitrina reciente', 'Producto reciente');

        $older->forceFill(['created_at' => now()->subDay()])->save();
        $newer->forceFill(['created_at' => now()])->save();

        Livewire::test(CatalogResults::class, [
            'type' => 'businesses',
            'municipalityId' => $municipality->id,
            'perPage' => 1,
        ])
            ->assertSee('Vitrina reciente')
            ->assertDontSee('Vitrina anterior')
            ->assertSee(__('Mostrando'))
            ->call('nextPage', 'page')
            ->assertSee('Vitrina anterior')
            ->assertDontSee('Vitrina reciente');
    }

    public function test_product_pages_update_inside_the_livewire_component(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $business = $this->publishedBusiness($municipality, $category, 'Vitrina', 'Producto anterior');
        $older = $business->products()->firstOrFail();
        $newer = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto reciente',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 3000,
        ], [], $business->organization->owner);

        $older->forceFill(['status' => 'publicado', 'created_at' => now()->subDay()])->save();
        $newer->forceFill(['status' => 'publicado', 'created_at' => now()])->save();

        Livewire::test(CatalogResults::class, [
            'type' => 'products',
            'municipalityId' => $municipality->id,
            'perPage' => 1,
        ])
            ->assertSee('Producto reciente')
            ->assertDontSee('Producto anterior')
            ->assertSee(__('Mostrando'))
            ->call('nextPage', 'productos_page')
            ->assertSee('Producto anterior')
            ->assertDontSee('Producto reciente');
    }

    /**
     * @return array{Municipality, Category}
     */
    private function catalogContext(): array
    {
        app()->setLocale('es');

        return [
            Municipality::create([
                'name' => 'Cajicá',
                'slug' => 'cajica',
                'department' => 'Cundinamarca',
                'is_active' => true,
            ]),
            Category::create([
                'name' => 'Alimentos',
                'slug' => 'alimentos',
                'is_active' => true,
            ]),
        ];
    }

    private function publishedBusiness(
        Municipality $municipality,
        Category $category,
        string $businessName,
        string $productName,
    ): Business {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => $businessName,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Descripción de prueba.',
        ])->business;

        $business->update(['logo_path' => 'businesses/1/logo.jpg']);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => $productName,
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 2000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }
}
