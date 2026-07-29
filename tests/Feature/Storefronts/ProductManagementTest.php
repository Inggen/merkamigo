<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.4 del TODO: gestión de productos y servicios.
 */
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_edit_and_archive_a_product(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Torta de chocolate')
            ->set('type', 'producto')
            ->set('price_type', 'exacto')
            ->set('price', 25000)
            ->set('photos', [UploadedFile::fake()->image('torta.jpg')])
            ->call('save');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'name' => 'Torta de chocolate',
        ]);

        $product = $business->products()->firstOrFail();
        $this->assertCount(1, $product->media);

        $component->call('openEdit', $product->id)
            ->set('price', 30000)
            ->call('save');

        $this->assertEquals(30000, $product->fresh()->price);

        $component->call('archive', $product->id);
        $this->assertSame('archivado', $product->fresh()->status);
    }

    public function test_a_collaborator_of_another_business_cannot_manage_products(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $businessA->id])
            ->assertForbidden();
    }

    public function test_owner_can_save_a_product_with_variants_and_an_active_promotion(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Café de la casa')
            ->set('type', 'producto')
            ->set('price_type', 'exacto')
            ->set('price', 5000)
            ->set('has_promo', true)
            ->set('promo_price', 4000)
            ->set('promo_label', 'Oferta de la semana')
            ->call('addVariant')
            ->set('variants.0.label', 'Grande')
            ->set('variants.0.price', 6000)
            ->call('addVariant')
            ->set('variants.1.label', 'Pequeño')
            ->call('save');

        $component->assertHasNoErrors();

        $product = $business->products()->where('name', 'Café de la casa')->firstOrFail();

        $this->assertTrue($product->hasActivePromo());
        $this->assertCount(2, $product->variants);
        $this->assertSame('Grande', $product->variants->first()->label);
        $this->assertEquals(6000, $product->variants->first()->price);
        $this->assertNull($product->variants->last()->price);
    }

    public function test_owner_can_duplicate_a_product_with_its_variants_and_photos(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Torta de chocolate')
            ->set('type', 'producto')
            ->set('price_type', 'exacto')
            ->set('price', 25000)
            ->call('addVariant')
            ->set('variants.0.label', 'Porción individual')
            ->set('photos', [UploadedFile::fake()->image('torta.jpg')])
            ->call('save');

        $original = $business->products()->where('name', 'Torta de chocolate')->firstOrFail();

        $component->call('duplicate', $original->id)->assertHasNoErrors();

        $this->assertSame(2, $business->products()->count());

        $duplicate = $business->products()->where('id', '!=', $original->id)->firstOrFail();

        $this->assertSame('borrador', $duplicate->status);
        $this->assertSame('Torta de chocolate', $duplicate->name);
        $this->assertNotSame($original->slug, $duplicate->slug);
        $this->assertCount(1, $duplicate->variants);
        $this->assertCount(1, $duplicate->media);
        Storage::disk('public')->assertExists($duplicate->media->first()->path);
    }

    public function test_a_sold_out_product_is_marked_as_such_publicly_and_not_available(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Pan francés')
            ->set('type', 'producto')
            ->set('price_type', 'exacto')
            ->set('price', 3000)
            ->set('is_available', false)
            ->call('save');

        $product = $business->products()->firstOrFail();

        $this->assertTrue($product->isSoldOut());
    }

    public function test_saving_a_product_with_a_link_in_the_description_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Producto con enlace')
            ->set('type', 'producto')
            ->set('description', 'Escríbenos a https://ejemplo.com para más info')
            ->call('save')
            ->assertHasErrors(['description']);

        $this->assertDatabaseMissing('products', ['name' => 'Producto con enlace']);
    }
}
