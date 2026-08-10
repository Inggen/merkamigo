<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandZone;
use App\Livewire\PlazaSpatialEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: poder agregar slots vacíos (sin negocio) desde
 * "Opciones" del editor espacial, tan directo como agregar cualquier otro
 * objeto — sin tener que elegir/crear una zona primero (eso queda oculto
 * detrás de `defaultStandZone()`). Solo deben verse en el editor, nunca en
 * la versión pública.
 */
class PlazaSpatialEditorAddSlotTest extends TestCase
{
    use RefreshDatabase;

    private function makePlaza(): ImmersivePlaza
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira-'.uniqid()]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'plaza-zipaquira-'.uniqid(),
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
        $experience->update(['status' => 'publicada']);

        return $plaza->fresh();
    }

    public function test_add_slot_works_with_no_zone_set_up_beforehand(): void
    {
        $plaza = $this->makePlaza();
        $this->assertDatabaseCount('stand_zones', 0);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot');

        $this->assertDatabaseCount('stand_slots', 1);

        $slot = StandZone::first()?->slots()->first();
        $this->assertNotNull($slot);
        $this->assertNull($slot->stand_template_id);
        $this->assertSame('disponible', $slot->status);
        $this->assertSame('manual', $slot->source);
        $this->assertGreaterThanOrEqual(-50, $slot->world_position['x']);
        $this->assertLessThanOrEqual(50, $slot->world_position['x']);

        $component->assertSet('selectedObjectType', 'slot');
        $component->assertSet('selectedObjectId', $slot->id);
    }

    public function test_the_auto_created_zone_is_reused_across_multiple_slots(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot')
            ->call('addSlot')
            ->call('addSlot');

        $this->assertDatabaseCount('stand_zones', 1);
        $this->assertDatabaseCount('stand_slots', 3);
    }

    /**
     * La zona automática es un contenedor técnico, no algo que el admin
     * haya delimitado — no debe dibujarse como un polígono más en el
     * visor 3D.
     */
    public function test_the_auto_created_zone_is_excluded_from_the_drawn_polygons(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot');

        $this->assertSame([], $component->get('sceneData')['zones']);
    }

    public function test_add_slot_is_undoable(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot');

        $this->assertDatabaseCount('stand_slots', 1);
        $this->assertTrue($component->instance()->canUndo());

        $component->call('undo');

        $this->assertDatabaseCount('stand_slots', 0);
    }

    public function test_undoing_then_redoing_an_added_slot_recreates_it(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot')
            ->call('undo');

        $this->assertDatabaseCount('stand_slots', 0);

        $component->call('redo');

        $this->assertDatabaseCount('stand_slots', 1);
    }

    /**
     * Un slot sin negocio asignado ya es invisible para el público por
     * diseño (`ImmersivePlazaStandsController` solo expone slots con
     * `assignment->isLive()`) — no debería hacer falta ningún filtro
     * nuevo para esto.
     */
    public function test_a_new_slot_is_not_visible_on_the_public_stands_endpoint(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('addSlot');

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands");

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }
}
