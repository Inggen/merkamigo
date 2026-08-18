<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Livewire\PlazaSpatialEditor;
use Database\Seeders\ImmersiveObjectTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: un "tipo de objeto especial" estrictamente
 * colisionante (semitransparente azul claro, bordes en azul más fuerte),
 * que se comporte como cualquier otro elemento del catálogo (duplicarse,
 * agregarse en el editor espacial) pero SIEMPRE bloquee el paso.
 */
class CollisionBarrierObjectTest extends TestCase
{
    use RefreshDatabase;

    private function makePlaza(): ImmersivePlaza
    {
        $municipality = Municipality::create(['name' => 'Test Muni', 'slug' => 'test-muni-'.uniqid()]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Test',
            'slug' => 'test-exp-'.uniqid(),
            'route_name' => 'labs.generic-plaza',
        ]);

        return $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
    }

    /**
     * La categoría "barrera" es un ENUM nativo (MySQL en producción,
     * recreación de tabla en SQLite para los tests) — este test es la
     * regresión real: la primera versión de esta migración usaba SQL
     * crudo específico de MySQL y rompía toda la suite en SQLite.
     */
    public function test_the_seeder_creates_a_published_collision_barrier_template(): void
    {
        $this->seed(ImmersiveObjectTemplateSeeder::class);

        $template = ImmersiveObjectTemplate::where('slug', 'barrera-de-colision')->first();

        $this->assertNotNull($template);
        $this->assertSame('barrera', $template->category);
        $this->assertSame('publicada', $template->status);
        $this->assertSame('collisionBarrier', $template->model_definition['boxes'][0]['texture']);
        $this->assertTrue($template->model_definition['boxes'][0]['collidable']);
    }

    public function test_the_collision_barrier_template_can_be_added_to_a_plaza(): void
    {
        $this->seed(ImmersiveObjectTemplateSeeder::class);
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::where('slug', 'barrera-de-colision')->firstOrFail();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('newPropTemplateId', $template->id)
            ->call('addProp')
            ->assertSet('selectedObjectType', 'prop');

        $prop = $plaza->fresh()->props()->latest('id')->first();

        $this->assertNotNull($prop);
        $this->assertSame($template->id, $prop->object_template_id);
    }
}
