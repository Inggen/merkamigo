<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Livewire\FavoriteButton;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.1.1/1.3 del TODO: guardar o quitar negocios y productos de favoritos.
 */
class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Favorita',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_a_client_can_favorite_and_unfavorite_a_business(): void
    {
        $business = $this->publishedBusiness();
        $client = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($client);

        Livewire::test(FavoriteButton::class, ['favoritable' => $business])
            ->assertSet('favorited', false)
            ->call('toggle')
            ->assertSet('favorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $business->getMorphClass(),
            'favoritable_id' => $business->id,
        ]);

        $this->actingAs($client)
            ->get(route('clientes.favoritos'))
            ->assertOk()
            ->assertSee('Panadería Favorita');

        Livewire::test(FavoriteButton::class, ['favoritable' => $business->fresh()])
            ->assertSet('favorited', true)
            ->call('toggle')
            ->assertSet('favorited', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => $business->getMorphClass(),
            'favoritable_id' => $business->id,
        ]);
    }

    public function test_a_guest_is_redirected_to_login_when_trying_to_favorite(): void
    {
        $business = $this->publishedBusiness();

        Livewire::test(FavoriteButton::class, ['favoritable' => $business])
            ->call('toggle')
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
