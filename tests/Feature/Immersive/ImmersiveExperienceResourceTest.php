<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Resources\ImmersiveExperiences\Pages\CreateImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\Pages\EditImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\Pages\ListImmersiveExperiences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMM-010/IMM-011 del TODO inmersivo.
 */
class ImmersiveExperienceResourceTest extends TestCase
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

    private function makeMunicipality(string $name): Municipality
    {
        return Municipality::create([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);
    }

    /**
     * IMM-010: desde `assertReadyToPublish()`, una experiencia solo se
     * puede publicar con `route_name` asignado y al menos una plaza con
     * punto de aparición + límites navegables. Este helper deja una
     * experiencia lista para publicar sin repetir el setup en cada test.
     */
    private function makeReadyPlaza(ImmersiveExperience $experience): ImmersivePlaza
    {
        return $experience->plazas()->create([
            'name' => 'Plaza 1',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
    }

    public function test_an_admin_can_create_an_immersive_experience_for_a_municipality(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $zipaquira = $this->makeMunicipality('Zipaquirá');

        Livewire::test(CreateImmersiveExperience::class)
            ->fillForm([
                'municipality_id' => $zipaquira->id,
                'name' => 'Plaza de Zipaquirá',
                'slug' => 'zipaquira',
                'status' => 'borrador',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('immersive_experiences', [
            'municipality_id' => $zipaquira->id,
            'slug' => 'zipaquira',
        ]);
    }

    public function test_an_admin_can_view_the_list_with_existing_records(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $municipality = $this->makeMunicipality('Tabio');
        ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Tabio',
            'slug' => 'tabio',
        ]);

        Livewire::test(ListImmersiveExperiences::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(ImmersiveExperience::all());
    }

    public function test_a_regular_user_cannot_access_the_resource(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(ListImmersiveExperiences::class)->assertForbidden();
    }

    public function test_creating_two_experiences_for_different_municipalities_never_mixes_them(): void
    {
        $zipaquira = $this->makeMunicipality('Zipaquirá');
        $cajica = $this->makeMunicipality('Cajicá');

        $zipaExperience = ImmersiveExperience::create([
            'municipality_id' => $zipaquira->id,
            'name' => 'Plaza de Zipaquirá',
            'slug' => 'zipaquira',
        ]);

        $cajicaExperience = ImmersiveExperience::create([
            'municipality_id' => $cajica->id,
            'name' => 'Parque de Cajicá',
            'slug' => 'cajica',
        ]);

        $this->assertSame($zipaquira->id, $zipaExperience->fresh()->municipality_id);
        $this->assertSame($cajica->id, $cajicaExperience->fresh()->municipality_id);
        $this->assertNotSame($zipaExperience->municipality_id, $cajicaExperience->municipality_id);
    }

    public function test_publishing_creates_a_version_snapshot_and_sets_the_published_version(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $municipality = $this->makeMunicipality('Chía');
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Chía',
            'slug' => 'chia',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($experience);

        Livewire::test(EditImmersiveExperience::class, ['record' => $experience->getRouteKey()])
            ->callAction('publish');

        $experience->refresh();

        $this->assertSame('publicada', $experience->status);
        $this->assertNotNull($experience->published_version_id);
        $this->assertDatabaseHas('experience_versions', [
            'immersive_experience_id' => $experience->id,
            'version' => 1,
            'status' => 'publicada',
        ]);
    }

    public function test_municipality_immersive_lab_url_resolves_from_the_published_experience(): void
    {
        $municipality = $this->makeMunicipality('Zipaquirá');

        $this->assertNull($municipality->immersiveLabUrl());

        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza voxel de Zipaquirá',
            'slug' => 'zipaquira',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($experience);
        $experience->update(['status' => 'publicada']);

        $this->assertSame(
            route('labs.zipa-inmersiva', ['municipio' => (string) $municipality->slug]),
            $municipality->fresh()->immersiveLabUrl()
        );
    }

    public function test_municipality_immersive_lab_url_is_null_without_a_published_experience(): void
    {
        $municipality = $this->makeMunicipality('Sopó');

        ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Borrador sin publicar',
            'slug' => 'sopo-borrador',
            'route_name' => 'labs.zipa-inmersiva',
            'status' => 'borrador',
        ]);

        $this->assertNull($municipality->fresh()->immersiveLabUrl());
    }

    public function test_a_municipality_cannot_have_two_published_experiences_at_once(): void
    {
        $municipality = $this->makeMunicipality('Cota');

        $first = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza principal',
            'slug' => 'cota-principal',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $this->makeReadyPlaza($first);
        $first->update(['status' => 'publicada']);

        $second = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza secundaria',
            'slug' => 'cota-secundaria',
            'route_name' => 'labs.cajica-inmersiva',
        ]);
        $this->makeReadyPlaza($second);

        $this->expectException(ValidationException::class);

        $second->update(['status' => 'publicada']);
    }
}
