<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandSlot;
use App\Livewire\PlazaSpatialEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: deshacer/rehacer en el editor espacial de la plaza,
 * igual que en el editor de cajas de un objeto.
 */
class PlazaSpatialEditorUndoRedoTest extends TestCase
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

        $plaza->zones()->create([
            'name' => 'Zona única',
            'polygon' => ['points' => [
                ['x' => -20, 'z' => -20], ['x' => 20, 'z' => -20], ['x' => 20, 'z' => 20], ['x' => -20, 'z' => 20],
            ]],
        ]);

        return $plaza->fresh(['zones']);
    }

    private function makeSlot(ImmersivePlaza $plaza, string $code): StandSlot
    {
        $zone = $plaza->zones->first();
        $index = $zone->slots()->count();

        return $zone->slots()->create([
            'code' => $code,
            'world_position' => ['x' => -15 + ($index * 10), 'y' => 0, 'z' => -15],
            'max_width' => 3.5,
            'max_depth' => 3.5,
        ]);
    }

    private function makePropTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Farol', 'slug' => 'farol-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'lamp',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 3, 'status' => 'publicada',
        ]);
    }

    public function test_undo_and_redo_are_disabled_with_no_history(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza]);

        $this->assertFalse($component->instance()->canUndo());
        $this->assertFalse($component->instance()->canRedo());
    }

    public function test_undo_restores_a_slot_position_moved_by_dragging(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $originalPosition = $slot->world_position;

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 3.0, 4.0);

        $this->assertEquals(3.0, $slot->fresh()->world_position['x']);
        $this->assertTrue($component->instance()->canUndo());

        $component->call('undo');

        $this->assertSame($originalPosition, $slot->fresh()->world_position);
        $this->assertTrue($component->instance()->canRedo());
    }

    public function test_redo_reapplies_the_undone_position(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 3.0, 4.0)
            ->call('undo');

        $component->call('redo');

        $this->assertEquals(3.0, $slot->fresh()->world_position['x']);
        $this->assertEquals(4.0, $slot->fresh()->world_position['z']);
    }

    public function test_undo_removes_a_prop_that_was_added(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('newPropTemplateId', $template->id)
            ->call('addProp');

        $this->assertCount(1, $plaza->fresh()->props);

        $component->call('undo');

        $this->assertCount(0, $plaza->fresh()->props);
    }

    public function test_redo_recreates_the_prop_removed_by_undo(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('newPropTemplateId', $template->id)
            ->call('addProp')
            ->call('undo');

        $this->assertCount(0, $plaza->fresh()->props);

        $component->call('redo');

        $this->assertCount(1, $plaza->fresh()->props);
    }

    /**
     * El caso inverso y más delicado: eliminar un elemento y deshacerlo
     * debe RECREARLO (no solo revertir un campo), incluyendo su posición.
     */
    public function test_undo_recreates_a_deleted_prop(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 5, 'y' => 0, 'z' => 5],
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('deleteProp', $prop->id);

        $this->assertCount(0, $plaza->fresh()->props);

        $component->call('undo');

        $restored = $plaza->fresh()->props()->first();
        $this->assertNotNull($restored);
        $this->assertEquals(5.0, $restored->world_position['x']);
    }

    public function test_undo_restores_bounds(): void
    {
        $plaza = $this->makePlaza();
        $originalBounds = $plaza->navigable_bounds;

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('boundsForm.maxX', 90)
            ->call('saveSpatialSettings');

        $this->assertEquals(90.0, $plaza->fresh()->navigable_bounds['maxX']);

        $component->call('undo');

        $this->assertEquals($originalBounds, $plaza->fresh()->navigable_bounds);
    }

    /**
     * El punto de aparición ahora se arrastra/edita como cualquier otro
     * objeto — su historial de deshacer debe funcionar igual.
     */
    public function test_undo_restores_the_spawn_point(): void
    {
        $plaza = $this->makePlaza();
        $originalSpawn = $plaza->spawn_point;

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSpawnPosition', 10.0, 20.0);

        $this->assertEquals(10.0, $plaza->fresh()->spawn_point['x']);

        $component->call('undo');

        $this->assertEquals($originalSpawn, $plaza->fresh()->spawn_point);
    }

    public function test_a_new_edit_after_undo_clears_the_redo_stack(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 3.0, 4.0)
            ->call('undo');

        $this->assertTrue($component->instance()->canRedo());

        $component->call('updateSlotPosition', $slot->id, 7.0, 8.0);

        $this->assertFalse($component->instance()->canRedo());
    }

    public function test_undo_is_a_no_op_when_there_is_nothing_to_undo(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $originalPosition = $slot->world_position;

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('undo');

        $this->assertSame($originalPosition, $slot->fresh()->world_position);
    }

    /**
     * Un movimiento rechazado por validación (fuera de la zona) nunca debe
     * generar una entrada de historial — no hay nada real que deshacer.
     */
    public function test_a_rejected_move_does_not_add_to_history(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 999.0, 999.0);

        $this->assertFalse($component->instance()->canUndo());
    }
}
