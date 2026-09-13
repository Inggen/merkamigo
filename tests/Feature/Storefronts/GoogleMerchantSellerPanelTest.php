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
 * Fase 9 de la integración con Google Merchant: el formulario del
 * emprendedor guarda los identificadores opcionales (GTIN/MPN/marca/
 * condición) y el badge de estado de Google aparece en su tarjeta.
 */
class GoogleMerchantSellerPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_optional_google_shopping_identifiers(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Test'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Jabón Artesanal Cookies & Cream')
            ->set('type', 'producto')
            ->set('price_type', 'exacto')
            ->set('price', 18000)
            ->set('gtin', '7701234567890')
            ->set('mpn', 'JAB-CC-01')
            ->set('brand', 'Cosmética Local')
            ->set('condition', 'nuevo')
            ->set('photos', [UploadedFile::fake()->image('jabon.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'name' => 'Jabón Artesanal Cookies & Cream',
            'gtin' => '7701234567890',
            'mpn' => 'JAB-CC-01',
            'brand' => 'Cosmética Local',
            'condition' => 'nuevo',
        ]);
    }

    public function test_google_status_badge_is_hidden_when_business_has_google_shopping_disabled(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Google'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->assertDontSee('Publicado en Google')
            ->assertDontSee('No publicado en Google');
    }
}
