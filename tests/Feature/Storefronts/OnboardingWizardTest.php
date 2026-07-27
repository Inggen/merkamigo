<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.2 del TODO: "Mi Merkamigo en cinco minutos".
 */
class OnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_walk_through_all_five_steps_and_publish(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('public');

        $municipality = Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Alimentos y bebidas', 'slug' => 'alimentos-y-bebidas', 'is_active' => true,
        ]);

        $component = Livewire::test('pages::emprendedores.crear-vitrina')
            ->set('name', 'Panadería Test')
            ->set('whatsapp_number', '+573001112233')
            ->set('municipality_id', $municipality->id)
            ->set('category_id', $category->id)
            ->call('goToStep2')
            ->assertSet('step', 2)
            ->set('description', 'La mejor panadería del barrio.')
            ->call('goToStep3')
            ->assertSet('step', 3)
            ->set('logo', UploadedFile::fake()->image('logo.jpg'))
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->call('goToStep4')
            ->assertSet('step', 4);

        $business = $user->fresh()->businesses()->first();
        $this->assertNotNull($business);
        $this->assertNotNull($business->logo_path);

        // Sin productos todavía: publicar debe fallar con la lista de faltantes.
        $component->call('goToStep5')->call('publish');
        $component->assertSet('missing', fn ($missing) => in_array('Al menos un producto o servicio', $missing));

        app(CreateProduct::class)->handle(
            $business,
            ['name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2000],
            [],
            $user,
        );

        $component->call('publish');

        $this->assertSame('publicado', $business->fresh()->status);
        $this->assertNotNull($business->fresh()->storefront->published_at);
    }
}
