<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 4 del TODO social (Sprint 4): Reels reutiliza `Post` con
 * `type = video`, sin un dominio paralelo — reacciones, comentarios y
 * seguir ya están cubiertos por `PostsTest`.
 */
class ReelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_owner_can_publish_a_reel(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $post = app(CreatePost::class)->handle($business, [
            'type' => 'video',
            'body' => 'Así preparamos nuestro pan.',
        ], [UploadedFile::fake()->create('reel.mp4', 2048, 'video/mp4')], $owner);

        $this->assertSame('video', $post->type);
        $this->assertCount(1, $post->media);
        $this->assertTrue($post->media->first()->isVideo());
    }

    public function test_a_video_post_requires_exactly_one_video_file(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $this->expectException(ValidationException::class);

        app(CreatePost::class)->handle($business, ['type' => 'video'], [], $owner);
    }

    public function test_the_reels_page_only_shows_video_posts(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Post de texto normal'], [], $owner);
        app(CreatePost::class)->handle($business, [
            'type' => 'video',
            'body' => 'Reel del negocio',
        ], [UploadedFile::fake()->create('reel.mp4', 2048, 'video/mp4')], $owner);

        $response = $this->get(route('reels'));

        $response->assertOk()
            ->assertSee('Reel del negocio')
            ->assertDontSee('Post de texto normal');
    }

    public function test_owner_can_publish_a_reel_from_the_panel(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.reels', ['business' => $business->id])
            ->set('body', 'Reel desde el panel')
            ->set('video', UploadedFile::fake()->create('reel.mp4', 2048, 'video/mp4'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $business->posts()->where('type', 'video')->count());
    }

    private function publishedBusiness(): Business
    {
        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería de prueba',
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

        return $business->fresh(['organization.owner']);
    }
}
