<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * `POST/GET/PATCH /api/v1/needs` (2.1 del TODO).
 */
class NeedsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_need_via_the_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $response = $this->postJson(route('api.v1.needs.store'), [
            'title' => 'Necesito un electricista',
            'description' => 'Se dañó un tomacorriente.',
            'municipality_id' => $municipality->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Necesito un electricista');
        $this->assertDatabaseHas('needs', ['user_id' => $user->id, 'status' => Need::BORRADOR]);
    }

    public function test_guests_cannot_create_a_need_via_the_api(): void
    {
        $this->postJson(route('api.v1.needs.store'), ['title' => 'X'])->assertUnauthorized();
    }

    public function test_a_user_can_view_and_update_their_own_need(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $created = $this->postJson(route('api.v1.needs.store'), [
            'title' => 'Original', 'description' => 'Descripción', 'municipality_id' => $municipality->id,
        ])->json('data');

        $this->getJson(route('api.v1.needs.show', $created['id']))
            ->assertOk()
            ->assertJsonPath('data.title', 'Original');

        $this->patchJson(route('api.v1.needs.update', $created['id']), ['title' => 'Actualizado'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Actualizado');
    }

    public function test_a_user_cannot_view_or_update_someone_elses_need(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = $this->postJson(route('api.v1.needs.store'), [
            'title' => 'Privado', 'description' => 'Descripción', 'municipality_id' => $municipality->id,
        ])->json('data');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.needs.show', $need['id']))->assertForbidden();
        $this->patchJson(route('api.v1.needs.update', $need['id']), ['title' => 'Hackeado'])->assertForbidden();
    }
}
