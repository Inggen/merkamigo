<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pedido explícito del usuario: poder "entrar" a la experiencia (la escena
 * 3D real, no solo la vista previa 2D) antes de publicarla. `?preview=1`
 * relaja el requisito de "experiencia publicada" en `PlazaController`, pero
 * SOLO para un administrador autenticado — nunca debe filtrar un borrador
 * al público.
 */
class ImmersivePlazaPreviewAccessTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    private function makeDraftExperienceWithActivePlaza(): ImmersiveExperience
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'is_active' => true]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza en borrador',
            'slug' => 'plaza-borrador',
            'route_name' => 'labs.generic-plaza',
        ]);
        $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);

        // A propósito NO se publica: $experience->status queda en 'borrador'.
        return $experience->fresh(['plazas']);
    }

    public function test_a_draft_experience_is_not_visible_without_the_preview_flag(): void
    {
        $this->makeDraftExperienceWithActivePlaza();

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->get(route('labs.generic-plaza', ['municipio' => 'zipaquira']))
            ->assertNotFound();
    }

    public function test_an_admin_can_preview_a_draft_experience_with_the_preview_flag(): void
    {
        $experience = $this->makeDraftExperienceWithActivePlaza();

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->get(route('labs.generic-plaza', ['municipio' => 'zipaquira', 'preview' => 1]))
            ->assertOk()
            ->assertViewHas('plaza', fn ($plaza) => $plaza->id === $experience->plazas->first()->id);
    }

    public function test_a_regular_authenticated_user_cannot_preview_a_draft_experience(): void
    {
        $this->makeDraftExperienceWithActivePlaza();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('labs.generic-plaza', ['municipio' => 'zipaquira', 'preview' => 1]))
            ->assertNotFound();
    }

    public function test_a_guest_cannot_preview_a_draft_experience(): void
    {
        $this->makeDraftExperienceWithActivePlaza();

        $this->get(route('labs.generic-plaza', ['municipio' => 'zipaquira', 'preview' => 1]))
            ->assertNotFound();
    }

    public function test_a_published_experience_still_resolves_normally_without_the_preview_flag(): void
    {
        $experience = $this->makeDraftExperienceWithActivePlaza();
        $experience->update(['status' => 'publicada']);

        $this->get(route('labs.generic-plaza', ['municipio' => 'zipaquira']))
            ->assertOk()
            ->assertViewHas('plaza', fn ($plaza) => $plaza->id === $experience->plazas->first()->id);
    }
}
