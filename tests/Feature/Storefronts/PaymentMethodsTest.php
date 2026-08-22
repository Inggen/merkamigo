<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\PaymentMethod;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: catálogo administrable de formas de pago con logo
 * que el negocio escoge y se muestran en su vitrina, en vez de solo el
 * texto libre de `payment_info`.
 */
class PaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    private function paymentMethod(string $name): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => 'payment-methods/'.Str::slug($name).'.svg',
            'is_active' => true,
            'position' => 0,
        ]);
    }

    public function test_owner_can_select_payment_methods_from_the_editor(): void
    {
        $nequi = $this->paymentMethod('Nequi');
        $visa = $this->paymentMethod('Visa');
        $this->paymentMethod('Mastercard');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Pagos',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('payment_method_ids', [$nequi->id, $visa->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$nequi->id, $visa->id], $business->fresh()->paymentMethods()->pluck('payment_methods.id')->sort()->values()->all());
    }

    public function test_selected_payment_methods_are_hydrated_back_into_the_editor(): void
    {
        $nequi = $this->paymentMethod('Nequi');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pagos 2'])->business;
        $business->paymentMethods()->sync([$nequi->id]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->assertSet('payment_method_ids', [$nequi->id]);
    }

    public function test_toggling_a_payment_method_autosaves(): void
    {
        $nequi = $this->paymentMethod('Nequi');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pagos 3'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('payment_method_ids', [$nequi->id]);

        $this->assertSame([$nequi->id], $business->fresh()->paymentMethods()->pluck('payment_methods.id')->all());
    }

    public function test_inactive_payment_methods_are_not_offered(): void
    {
        $active = $this->paymentMethod('Nequi');
        PaymentMethod::create(['name' => 'Vieja', 'slug' => 'vieja', 'is_active' => false, 'position' => 1]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pagos 4'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->assertSee('Nequi')
            ->assertDontSee('Vieja');
    }

    public function test_selected_payment_methods_show_up_with_their_logo_on_the_storefront(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        $nequi = $this->paymentMethod('Nequi');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pagos',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $business->paymentMethods()->sync([$nequi->id]);
        app(PublishStorefront::class)->handle($business, $owner);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Nequi')
            ->assertSee($nequi->logoUrl());

        $this->get(route('vitrinas.product', [$business, $product]))
            ->assertOk()
            ->assertSee('Nequi')
            ->assertSee($nequi->logoUrl());
    }

    public function test_the_payment_tab_falls_back_to_the_empty_state_without_any_payment_info(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Sin Pagos',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        $this->get(route('vitrinas.product', [$business, $product]))
            ->assertOk()
            ->assertSee('Todavía no hay información de pago');
    }
}
