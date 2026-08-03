<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    private function emprendedorPlan(): Plan
    {
        return Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Más productos, colaboradores y destacados.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7, 'max_storefronts' => 3],
            'trial_days' => 14,
            'is_active' => true,
            'position' => 1,
        ]);
    }

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

    public function test_owner_can_choose_which_storefront_a_new_product_belongs_to(): void
    {
        $owner = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio A'])->business;
        app(SubscribeToPlan::class)->handle($businessA, $this->emprendedorPlan(), $owner);
        $businessB = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio B'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $businessA->id])
            ->call('openCreate')
            ->set('productBusinessId', $businessB->id)
            ->set('name', 'Producto en B')
            ->set('type', 'producto')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'business_id' => $businessB->id,
            'name' => 'Producto en B',
        ]);
    }

    public function test_owner_can_move_an_existing_product_to_another_storefront(): void
    {
        $owner = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio A'])->business;
        app(SubscribeToPlan::class)->handle($businessA, $this->emprendedorPlan(), $owner);
        $businessB = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio B'])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.productos', ['business' => $businessA->id])
            ->call('openCreate')
            ->set('name', 'Producto a mover')
            ->set('type', 'producto')
            ->call('save');

        $product = $businessA->products()->where('name', 'Producto a mover')->firstOrFail();

        $component->call('openEdit', $product->id)
            ->set('productBusinessId', $businessB->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'business_id' => $businessB->id,
        ]);
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

    public function test_owner_can_set_alt_text_for_an_existing_product_photo_when_saving_the_product(): void
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

        $product = $business->products()->firstOrFail();
        $media = $product->media->first();

        $component->call('openEdit', $product->id)
            ->set("photoAlts.{$media->id}", 'Torta de chocolate con fresas')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Torta de chocolate con fresas', $media->fresh()->alt_text);
    }

    public function test_a_collaborator_of_another_business_cannot_remove_its_photos(): void
    {
        Storage::fake('public');

        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        Livewire::actingAs($ownerA);
        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $businessA->id])
            ->call('openCreate')
            ->set('name', 'Producto A')
            ->set('type', 'producto')
            ->set('photos', [UploadedFile::fake()->image('a.jpg')])
            ->call('save');

        $media = $businessA->products()->firstOrFail()->media->first();

        $ownerB = User::factory()->create();
        $businessB = app(CreateStorefront::class)->handle($ownerB, ['name' => 'Negocio B'])->business;

        $this->actingAs($ownerB);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $businessB->id])
            ->call('removeExistingMedia', $media->id);

        $this->assertNull($media->fresh()->alt_text);
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
