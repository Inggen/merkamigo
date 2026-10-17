<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\CreateStory;
use App\Domain\Social\Actions\RecordStoryView;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Livewire\CreateStoryModal;
use App\Livewire\StoriesRail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sprint 3 de TODO_social.md: Estados.
 */
class StoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_owner_can_create_a_story_that_expires_in_24_hours(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $story = app(CreateStory::class)->handle($business, [
            'type' => 'imagen',
            'caption' => 'Promo del día',
        ], UploadedFile::fake()->image('estado.jpg'), $owner);

        $this->assertSame($business->id, $story->business_id);
        $this->assertTrue($story->isActive());
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $story->expires_at->timestamp, 5);
    }

    /**
     * Pedido del usuario (2026-10-16): estados en video, además de
     * imagen. Mismo patrón que los reels (`PostMedia::isVideo()`): sin
     * columna de tipo propia, la extensión del archivo decide si se
     * guarda y sirve como video.
     */
    public function test_a_business_owner_can_create_a_video_story(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $story = app(CreateStory::class)->handle($business, [
            'type' => 'imagen',
            'caption' => 'Video del día',
        ], UploadedFile::fake()->create('estado.mp4', 2048, 'video/mp4'), $owner);

        $this->assertTrue($story->isVideo());
        Storage::disk('public')->assertExists($story->image_path);
    }

    public function test_a_story_can_link_an_existing_product_without_duplicating_it(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $product = $business->products()->firstOrFail();

        $story = app(CreateStory::class)->handle($business, [
            'type' => 'producto',
            'product_id' => $product->id,
        ], UploadedFile::fake()->image('estado.jpg'), $owner);

        $this->assertSame($product->id, $story->product_id);
        $this->assertSame(1, $business->products()->count());
    }

    public function test_viewing_a_story_is_recorded_once_per_user(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $story = app(CreateStory::class)->handle($business, ['type' => 'imagen'], UploadedFile::fake()->image('estado.jpg'), $owner);
        $visitor = User::factory()->create();

        $this->assertFalse($story->isViewedBy($visitor));

        app(RecordStoryView::class)->handle($visitor, $story);
        app(RecordStoryView::class)->handle($visitor, $story);

        $this->assertTrue($story->fresh()->isViewedBy($visitor));
        $this->assertSame(1, $story->fresh()->views_count);
    }

    public function test_an_expired_story_is_not_active(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $story = app(CreateStory::class)->handle($business, ['type' => 'imagen'], UploadedFile::fake()->image('estado.jpg'), $owner);
        $story->update(['expires_at' => now()->subMinute()]);

        $this->assertTrue($story->fresh()->isExpired());
        $this->assertFalse($story->fresh()->isActive());
    }

    public function test_the_feed_shows_the_stories_rail_for_a_business_with_an_active_story(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        app(CreateStory::class)->handle($business, ['type' => 'imagen'], UploadedFile::fake()->image('estado.jpg'), $owner);

        Livewire::test(StoriesRail::class)
            ->assertSee($business->name);
    }

    public function test_the_feed_only_shows_the_stories_card_when_there_are_active_stories(): void
    {
        Storage::fake('public');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('Historias'));

        $business = $this->publishedBusiness();
        app(CreateStory::class)->handle(
            $business,
            ['type' => 'imagen'],
            UploadedFile::fake()->image('estado-activo.jpg'),
            $business->organization->owner,
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('Historias'))
            ->assertSee($business->name);
    }

    /**
     * Pedido del usuario (2026-10-16): el dueño de un negocio debe poder
     * crear un estado desde Comunidad sin salir del feed, aunque todavía
     * no exista ningún estado activo en el sitio — antes la tarjeta
     * "Historias" se ocultaba por completo y no había dónde darle clic a
     * "Crear".
     */
    public function test_a_business_owner_sees_a_create_story_option_even_without_any_active_stories(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $this->actingAs($owner)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('Historias'))
            ->assertSee(__('Tu estado'));
    }

    /**
     * La modal de creación debe ser la misma que la de la zona de
     * emprendedores (`CreateStoryModal`) y, al publicar, el carrusel de
     * Comunidad (`StoriesRail`) debe enterarse vía el evento
     * `story-created` para mostrar el nuevo estado sin recargar.
     */
    public function test_a_business_owner_can_create_a_story_from_the_community_rail(): void
    {
        Storage::fake('public');

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;

        $this->actingAs($owner);

        Livewire::test(CreateStoryModal::class, ['business' => $business->id])
            ->set('caption', 'Desde Comunidad')
            ->set('photo', UploadedFile::fake()->image('estado.jpg'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('story-created');

        $story = $business->fresh()->stories()->firstOrFail();
        $this->assertSame('Desde Comunidad', $story->caption);

        Livewire::test(StoriesRail::class)
            ->assertSee($business->name);
    }

    public function test_owner_can_create_and_delete_a_story_from_the_panel(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Estados',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test(CreateStoryModal::class, ['business' => $business->id])
            ->assertSeeHtml('wire:model="photo"')
            ->set('type', 'imagen')
            ->set('caption', 'Nuevo estado')
            ->set('photo', UploadedFile::fake()->image('estado.jpg'))
            ->assertSeeHtml('wire:click="removePhoto"')
            ->assertDontSeeHtml('wire:model="photo"')
            ->call('removePhoto')
            ->assertSet('photo', null)
            ->assertSeeHtml('wire:model="photo"')
            ->set('photo', UploadedFile::fake()->image('estado.jpg'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('story-created');

        $story = $business->fresh()->stories()->firstOrFail();
        $this->assertSame('Nuevo estado', $story->caption);

        Livewire::test('pages::emprendedores.negocios.estados', ['business' => $business->id])
            ->call('delete', $story->id);

        $this->assertSoftDeleted('stories', ['id' => $story->id]);
    }

    public function test_owner_can_create_a_video_story_from_the_panel(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Estados Video',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test(CreateStoryModal::class, ['business' => $business->id])
            ->set('format', 'video')
            ->assertSeeHtml('wire:model="video"')
            ->assertDontSeeHtml('wire:model="photo"')
            ->set('caption', 'Reel del día')
            ->set('video', UploadedFile::fake()->create('estado.mp4', 2048, 'video/mp4'))
            ->call('save')
            ->assertHasNoErrors();

        $story = $business->fresh()->stories()->firstOrFail();
        $this->assertTrue($story->isVideo());
        $this->assertSame('Reel del día', $story->caption);
    }

    /**
     * Cambiar de formato a medio llenar un estado no debe dejar un
     * archivo "escondido" del formato anterior listo para publicarse
     * sin que el dueño lo note.
     */
    public function test_switching_format_clears_the_previously_selected_file(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Cambia Formato',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test(CreateStoryModal::class, ['business' => $business->id])
            ->set('photo', UploadedFile::fake()->image('estado.jpg'))
            ->set('format', 'video')
            ->assertSet('photo', null)
            ->set('video', UploadedFile::fake()->create('estado.mp4', 2048, 'video/mp4'))
            ->set('format', 'imagen')
            ->assertSet('video', null);
    }

    private function publishedBusiness(): Business
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
            'name' => "Negocio de prueba {$counter}",
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
