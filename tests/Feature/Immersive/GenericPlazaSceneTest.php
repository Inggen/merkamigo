<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `PlazaController::genericPlaza()`: la tercera opción de "Escena
 * inmersiva" — a diferencia de Zipaquirá/Cajicá (geometría fija escrita a
 * mano), arma el mundo caminable en vivo a partir de los datos de una
 * `ImmersivePlaza` cualquiera. Reutiliza `resolvePrimaryPlaza()` /
 * `canPreviewDraftExperience()`, ya cubiertos indirectamente por
 * ImmersivePlazaPreviewAccessTest sobre las otras rutas — este test cubre
 * la ruta genérica en sí, incluida su diferencia clave: sin plaza
 * resoluble, no hay una escena fija de respaldo que mostrar (404).
 */
class GenericPlazaSceneTest extends TestCase
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

    private function makeMunicipality(): Municipality
    {
        return Municipality::create(['name' => 'Chía', 'slug' => 'chia', 'is_active' => true]);
    }

    private function makeExperienceWithActivePlaza(Municipality $municipality): ImmersiveExperience
    {
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza genérica de prueba',
            'slug' => 'plaza-generica-chia',
            'route_name' => 'labs.generic-plaza',
        ]);
        $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 1, 'y' => 0, 'z' => 2, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -30, 'maxX' => 30, 'minZ' => -30, 'maxZ' => 30],
        ]);

        return $experience->fresh(['plazas']);
    }

    public function test_it_returns_404_when_the_municipality_has_no_experience_at_all(): void
    {
        $municipality = $this->makeMunicipality();

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertNotFound();
    }

    public function test_it_returns_404_for_an_inactive_municipality(): void
    {
        $municipality = Municipality::create(['name' => 'Chía', 'slug' => 'chia', 'is_active' => false]);
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertNotFound();
    }

    public function test_it_shows_the_active_plaza_of_a_published_experience(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertViewHas('plaza', fn ($plaza) => $plaza->id === $experience->plazas->first()->id)
            ->assertViewHas('municipio', fn ($resolvedMunicipio) => $resolvedMunicipio->id === $municipality->id);
    }

    public function test_it_exposes_the_editable_character_definitions_to_the_player(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);
        $definition = [
            'version' => 1,
            'boxes' => [[
                'x' => 7, 'y' => 2, 'z' => 0,
                'w' => 1, 'h' => 1, 'd' => 1,
                'texture' => 'coral',
            ]],
        ];
        ImmersiveObjectTemplate::create([
            'name' => 'Personaje personalizado',
            'slug' => 'personaje-voxel-hombre',
            'category' => 'personaje',
            'status' => 'publicada',
            'model_definition' => $definition,
        ]);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertViewHas('avatarDefinitions', fn (array $definitions): bool => $definitions['hombre'] === $definition)
            ->assertSee('window.genericAvatarDefinitions =', false)
            ->assertSee('"x":7', false);
    }

    /**
     * Pedido del usuario: imagen de cielo (spheremap/equirectangular) por
     * plaza, configurable desde "Editar Plaza" — sin imagen configurada,
     * la variable expuesta debe ser nula (la escena se ve como siempre).
     */
    public function test_it_exposes_a_null_sky_image_url_when_none_is_configured(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('window.genericPlazaSkyImageUrl = null;', false);
    }

    public function test_it_exposes_the_configured_sky_image_url(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);
        $experience->plazas->first()->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp']);

        $url = Storage::disk('public')->url('immersive-plazas/cielo-360.webp');

        // `@json()` escapa las barras (`\/`) al codificar el string — se
        // compara contra esa misma forma, no la URL cruda.
        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('window.genericPlazaSkyImageUrl = '.json_encode($url).';', false);
    }

    /**
     * Pedido del usuario: poder girar el fondo equirectangular hasta 360°
     * desde el editor espacial — la escena pública debe reflejar ese giro.
     */
    public function test_it_exposes_the_configured_sky_rotation(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);
        $experience->plazas->first()->update(['sky_rotation' => 275.5]);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('window.genericPlazaSkyRotation = 275.5;', false);
    }

    public function test_it_exposes_a_zero_sky_rotation_by_default(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);
        $experience->update(['status' => 'publicada']);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('window.genericPlazaSkyRotation = 0;', false);
    }

    public function test_a_draft_experience_returns_404_without_the_preview_flag(): void
    {
        $municipality = $this->makeMunicipality();
        $this->makeExperienceWithActivePlaza($municipality);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug]))
            ->assertNotFound();
    }

    public function test_an_admin_can_preview_a_draft_experience_with_the_preview_flag(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = $this->makeExperienceWithActivePlaza($municipality);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug, 'preview' => 1]))
            ->assertOk()
            ->assertViewHas('plaza', fn ($plaza) => $plaza->id === $experience->plazas->first()->id);
    }

    public function test_a_regular_authenticated_user_cannot_preview_a_draft_experience(): void
    {
        $municipality = $this->makeMunicipality();
        $this->makeExperienceWithActivePlaza($municipality);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug, 'preview' => 1]))
            ->assertNotFound();
    }

    /**
     * Bug real reportado por el usuario: una plaza nueva nace en "borrador"
     * (default de la columna) y normalmente nunca se activó todavía cuando
     * el admin quiere previsualizarla — `?preview=1` ya dejaba ver
     * experiencias sin publicar, pero seguía exigiendo `status = 'activa'`
     * en la plaza misma, dando 404 justo en el caso de uso principal de la
     * previsualización.
     */
    public function test_an_admin_can_preview_a_plaza_that_is_still_a_draft(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza genérica de prueba',
            'slug' => 'plaza-generica-chia',
            'route_name' => 'labs.generic-plaza',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'spawn_point' => ['x' => 1, 'y' => 0, 'z' => 2, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -30, 'maxX' => 30, 'minZ' => -30, 'maxZ' => 30],
        ]);
        $this->assertSame('borrador', $plaza->fresh()->status);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug, 'preview' => 1]))
            ->assertOk()
            ->assertViewHas('plaza', fn ($resolvedPlaza) => $resolvedPlaza->id === $plaza->id);
    }

    public function test_preview_still_excludes_an_archived_plaza(): void
    {
        $municipality = $this->makeMunicipality();
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza genérica de prueba',
            'slug' => 'plaza-generica-chia',
            'route_name' => 'labs.generic-plaza',
        ]);
        $experience->plazas()->create([
            'name' => 'Plaza vieja',
            'order' => 1,
            'status' => 'archivada',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -30, 'maxX' => 30, 'minZ' => -30, 'maxZ' => 30],
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->get(route('labs.generic-plaza', ['municipio' => $municipality->slug, 'preview' => 1]))
            ->assertNotFound();
    }
}
