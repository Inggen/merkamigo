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
}
