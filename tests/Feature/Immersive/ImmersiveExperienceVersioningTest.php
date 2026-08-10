<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Resources\ImmersiveExperiences\Pages\EditImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\RelationManagers\VersionsRelationManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMM-014 del TODO inmersivo: "borrador, previsualización, publicación y
 * reversión a versión anterior" — "una edición no afecta usuarios hasta
 * publicarse y puede revertirse".
 */
class ImmersiveExperienceVersioningTest extends TestCase
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

    /**
     * IMM-010: `publish()` exige `route_name` + al menos una plaza con
     * punto de aparición y límites navegables (`assertReadyToPublish()`).
     */
    private function makeReadyPlaza(ImmersiveExperience $experience): ImmersivePlaza
    {
        return $experience->plazas()->create([
            'name' => 'Plaza 1',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
    }

    public function test_revert_publishes_a_new_version_instead_of_rewriting_history(): void
    {
        $municipality = Municipality::create(['name' => 'Tenjo', 'slug' => 'tenjo']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Nombre original',
            'slug' => 'tenjo',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($experience);

        $v1 = $experience->publish(null);
        $this->assertSame(1, $v1->version);

        $experience->update(['name' => 'Nombre editado']);
        $v2 = $experience->publish(null);
        $this->assertSame(2, $v2->version);
        $this->assertSame('Nombre editado', $experience->fresh()->name);

        $v3 = $experience->fresh()->revertToVersion($v1, null);

        $this->assertSame(3, $v3->version, 'Revertir debe crear una versión nueva, no reescribir la 1.');
        $this->assertSame('Nombre original', $experience->fresh()->name);
        $this->assertSame($v3->id, $experience->fresh()->published_version_id);
        $this->assertDatabaseCount('experience_versions', 3);
    }

    public function test_revert_restores_spatial_fields_of_child_plazas(): void
    {
        $municipality = Municipality::create(['name' => 'Cota', 'slug' => 'cota']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Cota',
            'slug' => 'cota',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $plaza = ImmersivePlaza::create([
            'immersive_experience_id' => $experience->id,
            'name' => 'Plaza 1',
            'spawn_point' => ['x' => 1, 'y' => 0, 'z' => 1, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);

        $v1 = $experience->publish(null);

        $plaza->update(['spawn_point' => ['x' => 99, 'y' => 0, 'z' => 99, 'rotationY' => 0]]);
        $this->assertEquals(99, $plaza->fresh()->spawn_point['x']);

        $experience->fresh()->revertToVersion($v1, null);

        $this->assertEquals(1, $plaza->fresh()->spawn_point['x']);
    }

    public function test_the_revert_action_is_hidden_for_the_currently_published_version(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $municipality = Municipality::create(['name' => 'Nemocón', 'slug' => 'nemocon']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Nemocón',
            'slug' => 'nemocon',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($experience);
        $v1 = $experience->publish($admin);

        Livewire::test(EditImmersiveExperience::class, ['record' => $experience->getRouteKey()])
            ->assertSuccessful();

        Livewire::test(VersionsRelationManager::class, [
            'ownerRecord' => $experience,
            'pageClass' => EditImmersiveExperience::class,
        ])
            ->assertTableActionHidden('revert', $v1)
            ->assertTableActionVisible('preview', $v1);
    }

    public function test_the_revert_action_publishes_a_new_version_from_the_admin_ui(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $municipality = Municipality::create(['name' => 'Tabio', 'slug' => 'tabio']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Nombre v1',
            'slug' => 'tabio',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($experience);
        $v1 = $experience->publish($admin);

        $experience->update(['name' => 'Nombre v2']);
        $experience->publish($admin);

        Livewire::test(VersionsRelationManager::class, [
            'ownerRecord' => $experience->fresh(),
            'pageClass' => EditImmersiveExperience::class,
        ])
            ->callTableAction('revert', $v1)
            ->assertHasNoTableActionErrors();

        $this->assertSame('Nombre v1', $experience->fresh()->name);
        $this->assertDatabaseCount('experience_versions', 3);
    }

    /**
     * Un elemento en "borrador" solo se ve con `?preview=1`. Publicar la
     * experiencia es la señal de "esto ya está listo para el público", así
     * que debe confirmar en bloque los elementos pendientes de esa plaza —
     * si no, quedan invisibles hasta que alguien los edite uno por uno en
     * el recurso "Elementos de plaza".
     */
    public function test_publishing_confirms_pending_draft_props_of_its_plazas(): void
    {
        $municipality = Municipality::create(['name' => 'Cota', 'slug' => 'cota']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'cota',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $plaza = $this->makeReadyPlaza($experience);

        $template = ImmersiveObjectTemplate::create([
            'name' => 'Farol', 'slug' => 'farol-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'lamp',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 3, 'status' => 'publicada',
        ]);

        $draftProp = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 1, 'y' => 0, 'z' => 1],
            'status' => 'borrador',
        ]);
        $confirmedProp = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 2, 'y' => 0, 'z' => 2],
            'status' => 'confirmado',
        ]);

        $experience->publish(null);

        $this->assertSame('confirmado', $draftProp->fresh()->status);
        $this->assertSame('confirmado', $confirmedProp->fresh()->status);
    }
}
