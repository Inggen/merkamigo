<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_configures_social_content_highlights_and_google_maps_from_the_editor(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio con contenido',
        ])->business;
        $story = $business->stories()->create([
            'user_id' => $owner->id,
            'type' => 'imagen',
            'image_path' => 'stories/destacada.jpg',
            'caption' => 'Nuestra historia destacada',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('has_physical_location', true)
            ->set('show_posts', false)
            ->set('show_reels', true)
            ->set('google_maps_embed_url', '<iframe src="https://www.google.com/maps/embed?pb=ubicacion"></iframe>')
            ->call('save')
            ->call('toggleStoryHighlight', $story->id)
            ->assertHasNoErrors();

        $business->refresh();

        $this->assertFalse($business->storefront->show_posts);
        $this->assertTrue($business->storefront->show_reels);
        $this->assertSame('https://www.google.com/maps/embed?pb=ubicacion', $business->storefront->google_maps_embed_url);
        $this->assertTrue($story->fresh()->is_highlighted);
    }

    public function test_public_storefront_renders_selected_content_and_only_shows_map_for_a_physical_store(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio público con contenido',
            'has_physical_location' => true,
        ])->business;
        $business->update(['status' => 'publicado']);
        $business->storefront->update([
            'status' => 'publicado',
            'show_posts' => true,
            'show_reels' => true,
            'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=ubicacion',
        ]);

        $post = $business->posts()->create([
            'user_id' => $owner->id,
            'type' => 'texto',
            'body' => 'Publicación visible en la vitrina',
            'status' => 'publicado',
            'published_at' => now(),
        ]);
        $reel = $business->posts()->create([
            'user_id' => $owner->id,
            'type' => 'video',
            'body' => 'Reel visible en la vitrina',
            'status' => 'publicado',
            'published_at' => now(),
        ]);
        $reel->media()->create(['path' => 'posts/reel.mp4', 'position' => 0]);
        $business->stories()->create([
            'user_id' => $owner->id,
            'type' => 'imagen',
            'image_path' => 'stories/destacada.jpg',
            'caption' => 'Historia permanente en la vitrina',
            'expires_at' => now()->subWeek(),
            'is_highlighted' => true,
        ]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Publicación visible en la vitrina')
            ->assertSee('Reel visible en la vitrina')
            ->assertSee('Historia permanente en la vitrina')
            ->assertSee('https://www.google.com/maps/embed?pb=ubicacion', false);

        $business->update(['has_physical_location' => false]);
        $business->storefront->update(['show_posts' => false, 'show_reels' => false]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertDontSee($post->body)
            ->assertDontSee($reel->body)
            ->assertDontSee('https://www.google.com/maps/embed?pb=ubicacion', false)
            ->assertSee('Historia permanente en la vitrina');
    }

    public function test_google_maps_embed_rejects_non_google_iframes(): void
    {
        $this->assertNull(Storefront::normalizeGoogleMapsEmbedUrl(
            '<iframe src="https://example.com/maps/embed"></iframe>',
        ));
    }
}
