<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hermano de ImmersivePlazaStandsEndpointTest: el endpoint público que
 * `dynamic-stand-loader.js` consume para dibujar los elementos de plaza
 * (construcciones, árboles, fuentes, monumentos, personajes) ya colocados.
 */
class ImmersivePlazaPropsEndpointTest extends TestCase
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

    private function makePlaza(): ImmersivePlaza
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'plaza-cajica',
            'route_name' => 'labs.generic-plaza',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
        $experience->update(['status' => 'publicada']);

        return $plaza;
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Árbol', 'slug' => 'arbol-'.uniqid(), 'category' => 'arbol', 'builder_key' => 'tree',
            'max_width' => 4.2, 'max_depth' => 3.8, 'max_height' => 9, 'status' => 'publicada',
        ]);
    }

    public function test_a_confirmed_prop_appears_with_its_builder_key(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();

        $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 5, 'y' => 0, 'z' => -8],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'status' => 'confirmado',
        ]);

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('tree', $data[0]['builder_key']);
        $this->assertEquals(5, $data[0]['world_position']['x']);
    }

    public function test_a_prop_with_a_glb_exposes_its_model_url(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Fuente GLB', 'slug' => 'fuente-glb-'.uniqid(), 'category' => 'fuente',
            'builder_key' => 'fountain', 'model_path' => 'immersive-object-templates/models/fuente.glb',
            'max_width' => 5, 'max_depth' => 5, 'max_height' => 4, 'status' => 'publicada',
        ]);

        $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'collision_enabled' => true,
            'status' => 'confirmado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props")->json('data');

        $this->assertStringContainsString('immersive-object-templates/models/fuente.glb', $data[0]['model_url']);
        $this->assertTrue($data[0]['collision_enabled']);
    }

    public function test_a_draft_prop_does_not_appear(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();

        $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 5, 'y' => 0, 'z' => -8],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'status' => 'borrador',
        ]);

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props");

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_an_admin_preview_can_see_draft_props(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();

        $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 5, 'y' => 0, 'z' => -8],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'status' => 'borrador',
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props?preview=1");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * El tiling elegido en el editor espacial (Fase 4) debe llegar a la
     * plaza pública real — bug real de esta sesión: el editor lo aplicaba
     * visualmente pero este endpoint nunca lo exponía, así que
     * `dynamic-stand-loader.js` nunca lo aplicaba en la experiencia
     * inmersiva de verdad.
     */
    public function test_a_prop_exposes_its_chosen_texture_tiling(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();

        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'status' => 'confirmado',
        ]);
        $prop->update(['texture_tiling' => ['u' => 6, 'v' => 3]]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props")->json('data');

        $this->assertEquals(['u' => 6.0, 'v' => 3.0], $data[0]['tiling']);
    }

    public function test_a_prop_without_a_chosen_tiling_exposes_one_by_one(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();

        $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'status' => 'confirmado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props")->json('data');

        $this->assertEquals(['u' => 1.0, 'v' => 1.0], $data[0]['tiling']);
    }

    public function test_a_plaza_without_any_props_returns_an_empty_list(): void
    {
        $plaza = $this->makePlaza();

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/props");

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
