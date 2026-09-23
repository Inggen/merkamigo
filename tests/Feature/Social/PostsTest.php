<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Actions\ToggleFavorite;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Social\Actions\CreatePostComment;
use App\Domain\Social\Actions\ToggleFollowBusiness;
use App\Domain\Social\Actions\TogglePostReaction;
use App\Domain\Social\Notifications\NewPostPublished;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sprint 2 de TODO_social.md: posts, feed, reacciones, comentarios,
 * guardados y seguir negocios.
 */
class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_owner_can_create_a_text_post_and_it_publishes_immediately(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $post = app(CreatePost::class)->handle($business, [
            'type' => 'texto',
            'body' => 'Llegaron nuevos colores esta semana.',
        ], [], $owner);

        $this->assertSame('publicado', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame($business->id, $post->business_id);
        $this->assertSame($owner->id, $post->user_id);
    }

    public function test_a_post_without_body_or_photos_is_rejected(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $this->expectException(ValidationException::class);

        app(CreatePost::class)->handle($business, ['type' => 'texto'], [], $owner);
    }

    public function test_pending_post_photos_replace_the_input_and_can_be_removed(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.publicaciones', ['business' => $business->id])
            ->assertSeeHtml('wire:model="photos"')
            ->set('photos', [
                UploadedFile::fake()->image('uno.jpg'),
                UploadedFile::fake()->image('dos.jpg'),
            ])
            ->assertSeeHtml('wire:click="removePendingPhoto(0)"')
            ->assertSeeHtml('wire:click="removePendingPhoto(1)"')
            ->assertDontSeeHtml('wire:model="photos"')
            ->call('removePendingPhoto', 0)
            ->assertCount('photos', 1)
            ->call('removePendingPhoto', 0)
            ->assertSet('photos', [])
            ->assertSeeHtml('wire:model="photos"');
    }

    public function test_a_post_can_tag_an_existing_product_without_duplicating_it(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $product = $business->products()->firstOrFail();

        $post = app(CreatePost::class)->handle($business, [
            'type' => 'texto',
            'body' => 'Mira este producto.',
            'product_ids' => [$product->id],
        ], [], $owner);

        $this->assertSame([$product->id], $post->products->pluck('id')->all());
        $this->assertSame(1, $business->products()->count());
    }

    public function test_a_user_can_toggle_a_reaction_on_a_post(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        $this->assertTrue(app(TogglePostReaction::class)->handle($visitor, $post));
        $this->assertTrue($post->fresh()->isReactedBy($visitor));

        $this->assertFalse(app(TogglePostReaction::class)->handle($visitor, $post));
        $this->assertFalse($post->fresh()->isReactedBy($visitor));
    }

    public function test_a_user_can_comment_on_a_post(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        $comment = app(CreatePostComment::class)->handle($post, ['body' => '¡Se ve genial!'], $visitor);

        $this->assertSame('¡Se ve genial!', $comment->body);
        $this->assertTrue($comment->isVisible());
        $this->assertCount(1, $post->fresh()->visibleComments);
    }

    public function test_a_comment_with_a_link_is_rejected_same_as_the_rest_of_the_platform(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreatePostComment::class)->handle($post, ['body' => 'Visita https://spam.example'], $visitor);
    }

    public function test_a_post_can_be_saved_reusing_the_existing_favorite_mechanism(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        $this->assertFalse($post->isFavoritedBy($visitor));

        app(ToggleFavorite::class)->handle($visitor, $post);

        $this->assertTrue($post->fresh()->isFavoritedBy($visitor));
    }

    public function test_a_user_can_follow_and_unfollow_a_business(): void
    {
        $business = $this->publishedBusiness();
        $visitor = User::factory()->create();

        $this->assertTrue(app(ToggleFollowBusiness::class)->handle($visitor, $business));
        $this->assertTrue($visitor->isFollowing($business));
        $this->assertTrue($business->isFollowedBy($visitor));

        $this->assertFalse(app(ToggleFollowBusiness::class)->handle($visitor, $business));
        $this->assertFalse($visitor->fresh()->isFollowing($business));
    }

    public function test_publishing_notifies_every_registered_user(): void
    {
        Notification::fake();

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $users = User::factory()->count(3)->create();

        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Novedades'], [], $owner);

        foreach ($users->push($owner) as $user) {
            Notification::assertSentTo($user, NewPostPublished::class, function ($notification) use ($post, $user) {
                return $notification->toArray($user)['post_id'] === $post->id
                    && $notification->via($user) === ['database', PushChannel::class];
            });
        }

        Notification::assertCount(4);
    }

    public function test_a_draft_does_not_notify_registered_users(): void
    {
        Notification::fake();

        $business = $this->publishedBusiness();

        app(CreatePost::class)->handle($business, [
            'type' => 'texto',
            'body' => 'Todavía no se publica',
            'status' => 'borrador',
        ], [], $business->organization->owner);

        Notification::assertNothingSent();
    }

    public function test_the_feed_shows_recent_posts_from_the_users_municipality(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Post en Cajicá'], [], $owner);

        $response = $this->withCookie('municipio', $business->municipality->slug)
            ->get(route('feed'));

        $response->assertOk()->assertSee('Post en Cajicá');
    }

    /**
     * Decisión del usuario (sesión 15 sep 2026): el feed social es ahora
     * Inicio — `/feed` se conserva como alias, ver `routes/web.php`.
     */
    public function test_the_home_page_shows_the_feed(): void
    {
        config()->set('services.fcm.web', [
            'api_key' => 'web-key',
            'auth_domain' => 'merkamigo.firebaseapp.com',
            'project_id' => 'merkamigo',
            'storage_bucket' => null,
            'messaging_sender_id' => '123',
            'app_id' => 'app-id',
            'vapid_key' => 'vapid-key',
        ]);

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Post en Inicio'], [], $owner);
        $visitor = User::factory()->create(['experience' => 'cliente']);

        $response = $this->actingAs($visitor)
            ->withCookie('municipio', $business->municipality->slug)
            ->get(route('home'));

        $response->assertOk()
            ->assertSee('Post en Inicio')
            ->assertSee(__('Mi rol en Merkamigo'))
            ->assertSee(__('Comprador'))
            ->assertSee(__('Cerca de mí'))
            ->assertSee(__('Historias'))
            ->assertSee(__('Activar notificaciones'))
            ->assertSee(__('Publicaciones para ti'))
            ->assertSee(__('Más recientes'))
            ->assertSee(__('Negocios cerca de ti'))
            ->assertSee(__('Reels para ti'));
    }

    public function test_the_following_feed_tab_only_shows_posts_from_followed_businesses(): void
    {
        $followedBusiness = $this->publishedBusiness('Negocio Seguido');
        $otherBusiness = $this->publishedBusiness('Negocio No Seguido');
        app(CreatePost::class)->handle($followedBusiness, ['type' => 'texto', 'body' => 'Del negocio seguido'], [], $followedBusiness->organization->owner);
        app(CreatePost::class)->handle($otherBusiness, ['type' => 'texto', 'body' => 'Del negocio no seguido'], [], $otherBusiness->organization->owner);

        $visitor = User::factory()->create();
        app(ToggleFollowBusiness::class)->handle($visitor, $followedBusiness);

        $response = $this->actingAs($visitor)->get(route('feed', ['tab' => 'siguiendo']));

        $response->assertOk()
            ->assertSee('Del negocio seguido')
            ->assertDontSee('Del negocio no seguido');
    }

    private function publishedBusiness(string $name = 'Negocio de prueba'): Business
    {
        static $counter = 0;
        $counter++;

        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'alimentos'],
            ['name' => 'Alimentos', 'is_active' => true],
        );

        $owner = User::factory()->create();
        $storefront = app(CreateStorefront::class)->handle($owner, [
            'name' => "{$name} {$counter}",
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ]);
        $business = $storefront->business;
        $business->update(['status' => 'publicado']);

        $business->products()->create([
            'name' => "Producto {$counter}",
            'slug' => "producto-{$counter}",
            'type' => 'producto',
            'price' => 10000,
            'price_type' => 'exacto',
            'status' => 'publicado',
        ]);

        return $business->fresh(['organization.owner', 'municipality']);
    }
}
