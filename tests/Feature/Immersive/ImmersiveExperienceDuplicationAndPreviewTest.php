<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveExperiences\Pages\ListImmersiveExperiences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMM-010 del TODO inmersivo: "el administrador crea, edita, duplica,
 * previsualiza, publica y archiva una experiencia" — cierre de los tres
 * pendientes: duplicar, validar antes de publicar y previsualizar.
 */
class ImmersiveExperienceDuplicationAndPreviewTest extends TestCase
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

    private function buildExperienceWithLayout(): ImmersiveExperience
    {
        $municipality = Municipality::create(['name' => 'Gachancipá', 'slug' => 'gachancipa']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Gachancipá',
            'slug' => 'gachancipa',
            'route_name' => 'labs.generic-plaza',
        ]);

        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);

        $zone = $plaza->zones()->create([
            'name' => 'Zona norte',
            'polygon' => ['points' => [
                ['x' => -20, 'z' => -20], ['x' => 20, 'z' => -20], ['x' => 20, 'z' => 20], ['x' => -20, 'z' => 20],
            ]],
        ]);

        $template = ImmersiveObjectTemplate::create([
            'name' => 'Stand estándar', 'slug' => 'stand-estandar', 'category' => 'stand',
            'max_width' => 4, 'max_depth' => 4, 'max_height' => 3,
        ]);

        $zone->slots()->create([
            'code' => 'S1',
            'stand_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'max_width' => 4,
            'max_depth' => 4,
        ]);

        $treeTemplate = ImmersiveObjectTemplate::create([
            'name' => 'Árbol', 'slug' => 'arbol', 'category' => 'arbol',
            'max_width' => 2, 'max_depth' => 2, 'max_height' => 6,
        ]);

        $plaza->props()->create([
            'object_template_id' => $treeTemplate->id,
            'world_position' => ['x' => -10, 'y' => 0, 'z' => -10],
        ]);

        return $experience;
    }

    public function test_duplicate_clones_experience_plazas_zones_slots_and_props(): void
    {
        $experience = $this->buildExperienceWithLayout();

        $copy = $experience->duplicate();

        $this->assertNotSame($experience->id, $copy->id);
        $this->assertSame('borrador', $copy->status);
        $this->assertSame('Plaza de Gachancipá (copia)', $copy->name);
        $this->assertSame('gachancipa-copia', $copy->slug);

        $this->assertCount(1, $copy->plazas);
        $plazaCopy = $copy->plazas->first();
        $this->assertSame('borrador', $plazaCopy->status);

        $this->assertCount(1, $plazaCopy->zones);
        $zoneCopy = $plazaCopy->zones->first();
        $this->assertCount(1, $zoneCopy->slots);
        $this->assertSame('S1', $zoneCopy->slots->first()->code);
        $this->assertSame('manual', $zoneCopy->slots->first()->source);

        $this->assertCount(1, $plazaCopy->props);

        // El original no se toca.
        $this->assertCount(1, $experience->fresh()->plazas);
    }

    public function test_duplicating_twice_produces_unique_slugs(): void
    {
        $experience = $this->buildExperienceWithLayout();

        $first = $experience->duplicate();
        $second = $experience->duplicate();

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame('gachancipa-copia', $first->slug);
        $this->assertSame('gachancipa-copia-2', $second->slug);
    }

    public function test_the_duplicate_action_is_available_from_the_experiences_list(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $experience = $this->buildExperienceWithLayout();

        Livewire::test(ListImmersiveExperiences::class)
            ->callTableAction('duplicate', $experience)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('immersive_experiences', ['slug' => 'gachancipa-copia']);
    }

    public function test_publish_fails_without_a_scene_assigned(): void
    {
        $municipality = Municipality::create(['name' => 'Cogua', 'slug' => 'cogua']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Cogua',
            'slug' => 'cogua',
        ]);
        $experience->plazas()->create([
            'name' => 'Plaza 1',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se puede publicar sin asignar una escena inmersiva.');

        $experience->publish(null);
    }

    public function test_publish_fails_without_any_ready_plaza(): void
    {
        $municipality = Municipality::create(['name' => 'Tocancipá', 'slug' => 'tocancipa']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Tocancipá',
            'slug' => 'tocancipa',
            'route_name' => 'labs.generic-plaza',
        ]);

        // Plaza sin límites navegables: no cuenta como "lista".
        $experience->plazas()->create([
            'name' => 'Plaza 1',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se puede publicar sin al menos una plaza con punto de aparición y límites navegables definidos.');

        $experience->publish(null);
    }

    /**
     * Regresión: Eloquent dispara `saving()` en TODO `save()`, tenga o no
     * cambios. Sin el resguardo `isDirty('status')`, editar cualquier otro
     * campo de una experiencia ya publicada — o simplemente re-ejecutar un
     * seeder idempotente — fallaba la validación de "plaza lista" aunque
     * nada relacionado con publicar hubiera cambiado. Reproduce el bug real
     * encontrado al re-correr `ImmersiveExperienceSeeder` sobre datos ya
     * publicados sin plaza.
     */
    public function test_resaving_an_already_published_experience_does_not_require_a_ready_plaza_again(): void
    {
        $experience = $this->buildExperienceWithLayout();
        $experience->publish(null);

        // Simula el estado real encontrado: publicada, pero sin ninguna
        // plaza lista (como quedaron los registros seeded antes de que
        // existiera esta validación).
        $experience->plazas()->update(['navigable_bounds' => null]);

        $experience->description = 'Actualizando un campo cualquiera';
        $experience->save();

        $this->assertSame('Actualizando un campo cualquiera', $experience->fresh()->description);
        $this->assertSame('publicada', $experience->fresh()->status);
    }

    public function test_a_failed_publish_does_not_leave_an_orphaned_version(): void
    {
        $municipality = Municipality::create(['name' => 'Sopó', 'slug' => 'sopo-2']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Sin escena',
            'slug' => 'sin-escena',
        ]);

        try {
            $experience->publish(null);
        } catch (ValidationException) {
            // esperado
        }

        $this->assertDatabaseCount('experience_versions', 0);
    }
}
