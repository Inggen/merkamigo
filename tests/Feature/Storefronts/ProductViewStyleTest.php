<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\UpdateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductViewStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_style_is_classic_and_owner_can_autosave_and_reopen_elegant(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Vista configurable'])->business;
        $this->assertSame('classic', $business->storefront->fresh()->product_view_style);
        $this->actingAs($owner);
        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('product_view_style', 'elegant')->assertHasNoErrors();
        $this->assertSame('elegant', $business->storefront->fresh()->product_view_style);
        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->assertSet('product_view_style', 'elegant');
    }

    public function test_unknown_styles_cannot_be_saved(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Estilo válido'])->business;
        $this->expectException(ValidationException::class);
        app(UpdateStorefront::class)->handle($business, ['product_view_style' => 'invented'], $owner);
    }

    public function test_classic_and_elegant_keep_product_details_payment_tabs_and_gallery(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Tienda de prueba'])->business;
        $business->update(['status' => 'publicado', 'payment_info' => 'Aceptamos pago en efectivo.']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto de galería', 'description' => 'Descripción legible del producto.',
            'type' => 'producto', 'price_type' => 'exacto', 'price' => 25000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $product->media()->create(['path' => 'tests/white.jpg', 'type' => 'image', 'position' => 0]);
        $product->media()->create(['path' => 'tests/black.jpg', 'type' => 'image', 'position' => 1]);
        $product->media()->create(['path' => 'tests/video.mp4', 'type' => 'video', 'position' => 2]);
        $url = route('vitrinas.product', [$business, $product]);
        $this->get($url)->assertOk()->assertDontSee('elegant-product-hero', false)->assertSee('Descripción legible del producto.');
        $business->storefront->update(['product_view_style' => 'elegant']);
        $this->get($url)->assertOk()->assertSee('elegant-product-hero', false)
            ->assertSee('tests/white.jpg')->assertSee('tests/black.jpg')->assertSee('tests/video.mp4')
            ->assertSee('Cantidad')->assertSee('Información de pago')->assertSee('Reseñas')
            ->assertSee('Aceptamos pago en efectivo.')->assertSee('Ver tienda');
    }

    public function test_another_user_cannot_change_a_storefront_style(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Privada'])->business;
        $this->actingAs(User::factory()->create());
        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])->assertForbidden();
        $this->assertSame('classic', $business->storefront->fresh()->product_view_style);
    }
}
