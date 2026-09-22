<?php

namespace Tests\Feature\Social;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sprint 2 de TODO_social.md: "Publicaciones" del panel del emprendedor.
 */
class PublicationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_text_post_from_the_panel(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Publicaciones',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.publicaciones', ['business' => $business->id])
            ->set('type', 'texto')
            ->set('body', 'Nuestra promoción de la semana.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nuestra promoción de la semana.', $business->fresh()->posts()->firstOrFail()->body);
    }

    public function test_owner_can_delete_a_post(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Publicaciones',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.publicaciones', ['business' => $business->id])
            ->set('type', 'texto')
            ->set('body', 'Post a borrar')
            ->call('save');

        $post = $business->fresh()->posts()->firstOrFail();

        $component->call('delete', $post->id);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_page(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Ajeno',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $outsider = User::factory()->create();
        $this->actingAs($outsider);

        Livewire::test('pages::emprendedores.negocios.publicaciones', ['business' => $business->id])
            ->assertForbidden();
    }
}
