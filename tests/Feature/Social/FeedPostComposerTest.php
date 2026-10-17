<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Social\Models\Post;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Livewire\FeedPostComposer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FeedPostComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_with_a_published_storefront_can_publish_directly_from_the_feed(): void
    {
        Notification::fake();
        Storage::fake('public');

        [$owner, $business] = $this->businessFor('Vitrina publicada', 'publicado');
        $product = $business->products()->create([
            'name' => 'Producto destacado',
            'slug' => 'producto-destacado',
            'type' => 'producto',
            'price_type' => 'consultar',
            'status' => 'publicado',
        ]);

        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->assertSet('businessId', $business->id)
            ->assertSee(__('Comparte desde tu vitrina'))
            ->assertSee($business->name)
            ->set('body', 'Llegó una novedad para nuestra comunidad.')
            ->set('productIds', [$product->id])
            ->set('photos', [UploadedFile::fake()->image('novedad.jpg')])
            ->call('publish')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $post = Post::query()->firstOrFail();

        $this->assertSame($business->id, $post->business_id);
        $this->assertSame($owner->id, $post->user_id);
        $this->assertSame('imagen', $post->type);
        $this->assertSame('publicado', $post->status);
        $this->assertSame([$product->id], $post->products()->pluck('products.id')->all());
        $this->assertCount(1, $post->media);
    }

    public function test_the_feed_only_shows_the_composer_to_users_who_manage_a_published_storefront(): void
    {
        [$owner] = $this->businessFor('Vitrina publicada', 'publicado');
        [$draftOwner] = $this->businessFor('Vitrina en borrador', 'borrador');
        $customer = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-feed-post-composer', false);

        $this->actingAs($draftOwner)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-feed-post-composer', false);

        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-feed-post-composer', false);
    }

    public function test_a_user_cannot_tamper_with_the_business_id_to_publish_for_another_storefront(): void
    {
        Notification::fake();

        [$owner] = $this->businessFor('Mi vitrina en borrador', 'borrador');
        [, $otherBusiness] = $this->businessFor('Vitrina ajena', 'publicado');

        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->set('businessId', $otherBusiness->id)
            ->set('body', 'Intento no autorizado')
            ->call('publish')
            ->assertForbidden();

        $this->assertDatabaseMissing('posts', ['body' => 'Intento no autorizado']);
    }

    public function test_the_composer_offers_emojis_multimedia_events_and_rewards(): void
    {
        [$owner, $business] = $this->businessFor('Vitrina con contenido', 'publicado');
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->assertSee(__('Emoticón'))
            ->assertSee(__('Multimedia'))
            ->assertSee(__('Evento'))
            ->assertSee(__('Recompensa'))
            ->call('appendEmoji', '🎉')
            ->assertSet('body', ' 🎉');
    }

    public function test_an_owner_can_publish_a_video_from_the_feed(): void
    {
        Notification::fake();
        Storage::fake('public');

        [$owner, $business] = $this->businessFor('Vitrina con video', 'publicado');
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->set('body', 'Mira cómo hacemos nuestro trabajo.')
            ->set('video', UploadedFile::fake()->create('proceso.mp4', 1024, 'video/mp4'))
            ->call('publish')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $post = $business->posts()->firstOrFail();
        $this->assertSame('video', $post->type);
        $this->assertCount(1, $post->media);
    }

    public function test_an_owner_can_publish_an_upcoming_event_from_the_feed(): void
    {
        Notification::fake();

        [$owner, $business] = $this->businessFor('Vitrina con evento', 'publicado');
        $event = $business->publicEvents()->create([
            'title' => 'Taller local',
            'slug' => 'taller-local-feed',
            'starts_at' => now()->addWeek(),
            'status' => PublicEvent::PUBLICADO,
            'created_by_user_id' => $owner->id,
        ]);
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->call('selectEvent', $event->id)
            ->assertSet('eventId', $event->id)
            ->call('publish')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('posts', [
            'business_id' => $business->id,
            'public_event_id' => $event->id,
            'status' => 'publicado',
        ]);
    }

    public function test_an_owner_can_attach_an_active_reward_to_a_post(): void
    {
        Notification::fake();

        [$owner, $business] = $this->businessFor('Vitrina con premio', 'publicado');
        $reward = LoyaltyReward::create([
            'business_id' => $business->id,
            'type' => LoyaltyReward::REGALO,
            'title' => 'Café gratis',
            'points_cost' => 400,
            'full_cost_cents' => 800000,
            'stock_total' => 20,
            'status' => LoyaltyReward::PUBLICADO,
            'created_by_user_id' => $owner->id,
        ]);
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->call('selectReward', $reward->id)
            ->assertSet('rewardId', $reward->id)
            ->call('publish')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('posts', [
            'business_id' => $business->id,
            'loyalty_reward_id' => $reward->id,
            'status' => 'publicado',
        ]);
    }

    /**
     * Si el negocio todavía no tiene eventos, recompensas o productos
     * configurados, el panel vacío debe ofrecer un botón que lleve
     * directo a crearlos — no solo un texto sin acción.
     */
    public function test_the_composer_offers_buttons_to_create_events_rewards_and_products_when_there_are_none(): void
    {
        [$owner, $business] = $this->businessFor('Vitrina sin contenido', 'publicado');
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->assertSee(__('No tienes eventos próximos disponibles para publicar.'))
            ->assertSee(__('Crear un evento'))
            ->assertSeeHtml(route('emprendedores.negocios.eventos', $business))
            ->assertSee(__('No tienes recompensas activas disponibles para publicar.'))
            ->assertSee(__('Crear una recompensa'))
            ->assertSeeHtml(route('emprendedores.negocios.merkapuntos', $business))
            ->assertSee(__('No tienes productos publicados para etiquetar.'))
            ->assertSee(__('Crear un producto'))
            ->assertSeeHtml(route('emprendedores.negocios.productos', $business));
    }

    public function test_the_composer_shows_product_cards_with_image_and_price_instead_of_a_create_button_once_products_exist(): void
    {
        [$owner, $business] = $this->businessFor('Vitrina con productos', 'publicado');
        $business->products()->create([
            'name' => 'Cortina Vintage',
            'slug' => 'cortina-vintage',
            'type' => 'producto',
            'price' => 120000,
            'price_type' => 'desde',
            'status' => 'publicado',
            'is_available' => true,
        ]);
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->assertSee('Cortina Vintage')
            ->assertSee(__('Desde').' $120.000')
            ->assertDontSee(__('Crear un producto'));
    }

    /**
     * Un negocio con catálogos grandes (50+ productos) necesita poder
     * encontrar uno sin desplazarse por toda la lista.
     */
    public function test_the_composer_lets_the_owner_search_products_when_the_catalog_is_large(): void
    {
        [$owner, $business] = $this->businessFor('Vitrina con catálogo grande', 'publicado');
        foreach (range(1, 7) as $index) {
            $business->products()->create([
                'name' => "Cortina {$index}",
                'slug' => "cortina-{$index}",
                'type' => 'producto',
                'price' => 50000,
                'price_type' => 'desde',
                'status' => 'publicado',
                'is_available' => true,
            ]);
        }
        $business->products()->create([
            'name' => 'Persiana Enrollable',
            'slug' => 'persiana-enrollable',
            'type' => 'producto',
            'price' => 90000,
            'price_type' => 'desde',
            'status' => 'publicado',
            'is_available' => true,
        ]);
        $this->actingAs($owner);

        Livewire::test(FeedPostComposer::class)
            ->assertSee(__('Buscar producto…'))
            ->assertSee('Cortina 1')
            ->assertSee('Persiana Enrollable')
            ->set('productSearch', 'persiana')
            ->assertSee('Persiana Enrollable')
            ->assertDontSee('Cortina 1')
            ->set('productSearch', 'algo-que-no-existe')
            ->assertSee(__('Ningún producto coincide con ":busqueda".', ['busqueda' => 'algo-que-no-existe']));
    }

    /** @return array{User, Business} */
    private function businessFor(string $name, string $status): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => $name,
            'whatsapp_number' => '+573001112233',
        ])->business;
        $business->update(['status' => $status]);

        return [$owner, $business->fresh(['organization', 'storefront'])];
    }
}
