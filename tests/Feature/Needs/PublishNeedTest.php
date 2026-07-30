<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2.1 del TODO: "Pídelo en Merkamigo" — publicación de necesidades.
 */
class PublishNeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_can_save_a_draft_add_photos_and_publish(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Storage::fake('public');

        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Hogar', 'slug' => 'hogar', 'is_active' => true]);

        $component = Livewire::test('pages::pidelo.nueva')
            ->set('title', 'Necesito un plomero')
            ->set('description', 'Se dañó la tubería de la cocina.')
            ->set('municipality_id', $municipality->id)
            ->set('category_id', $category->id)
            ->set('zone', 'Centro')
            ->assertSet('savedAt', fn ($savedAt) => $savedAt !== null);

        $need = Need::firstOrFail();
        $this->assertSame($user->id, $need->user_id);
        $this->assertSame(Need::BORRADOR, $need->status);
        $this->assertSame('Necesito un plomero', $need->title);

        $component->set('photos', [UploadedFile::fake()->image('foto.jpg')]);
        $this->assertCount(1, $need->fresh()->media);

        $component->call('togglePreview')->call('publish');

        $need->refresh();
        $this->assertSame(Need::PUBLICADA, $need->status);
        $this->assertNotNull($need->published_at);
        $this->assertNotNull($need->expires_at);
        $this->assertTrue($need->expires_at->isFuture());
    }

    public function test_publishing_without_the_minimum_fields_shows_what_is_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test('pages::pidelo.nueva')
            ->set('title', 'Algo')
            ->call('togglePreview')
            ->call('publish');

        $component->assertSet('missing', fn ($missing) => in_array('Descripción', $missing) && in_array('Municipio', $missing));
        $this->assertSame(Need::BORRADOR, Need::firstOrFail()->status);
    }

    public function test_a_description_with_a_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::pidelo.nueva')
            ->set('title', 'Necesito algo')
            ->set('description', 'Mira esto https://spam.example.com')
            ->assertHasErrors(['description']);

        $this->assertDatabaseMissing('needs', ['description' => 'Mira esto https://spam.example.com']);
    }

    public function test_returning_to_the_page_resumes_the_most_recent_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::pidelo.nueva')->set('title', 'Borrador existente');
        $need = Need::firstOrFail();

        Livewire::test('pages::pidelo.nueva')->assertSet('needId', $need->id)->assertSet('title', 'Borrador existente');
    }
}
