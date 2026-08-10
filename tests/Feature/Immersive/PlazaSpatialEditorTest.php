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
 * Editor espacial 3D de una plaza: reposicionar (arrastrar) stands y
 * elementos existentes en vez de escribir x/y/z a ciegas en un formulario.
 */
class PlazaSpatialEditorTest extends TestCase
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

    private function makeSlot(ImmersivePlaza $plaza, string $code, ?int $templateId = null): StandSlot
    {
        $zone = $plaza->zones->first();
        $index = $zone->slots()->count();

        return $zone->slots()->create([
            'code' => $code,
            'stand_template_id' => $templateId,
            'world_position' => ['x' => -15 + ($index * 10), 'y' => 0, 'z' => -15],
            'max_width' => 3.5,
            'max_depth' => 3.5,
        ]);
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Caseta de madera', 'slug' => 'caseta-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standBooth',
            'max_width' => 4.2, 'max_depth' => 3.8, 'max_height' => 2.9, 'status' => 'publicada',
        ]);
    }

    public function test_mount_builds_scene_data_with_the_expected_slots_and_props(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();
        $slot = $this->makeSlot($plaza, 'S1', $template->id);

        $propTemplate = ImmersiveObjectTemplate::create([
            'name' => 'Farol', 'slug' => 'farol-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'lamp',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 3, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $propTemplate->id,
            'world_position' => ['x' => 5, 'y' => 0, 'z' => 5],
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza]);

        $scene = $component->get('sceneData');

        $this->assertCount(1, $scene['slots']);
        $this->assertSame($slot->id, $scene['slots'][0]['id']);
        $this->assertSame('standBooth', $scene['slots'][0]['builderKey']);

        $this->assertCount(1, $scene['props']);
        $this->assertSame($prop->id, $scene['props'][0]['id']);
        $this->assertEquals(5.0, $scene['props'][0]['x']);
    }

    public function test_update_slot_position_persists_a_valid_position(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 3.0, 4.0)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $slot->fresh();
        $this->assertEquals(3.0, $fresh->world_position['x']);
        $this->assertEquals(4.0, $fresh->world_position['z']);
    }

    public function test_update_slot_position_rejects_a_position_outside_its_zone(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $originalPosition = $slot->world_position;

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 999.0, 999.0)
            ->assertDispatched('spatial-editor-reject', type: 'slot', id: $slot->id);

        $this->assertSame($originalPosition, $slot->fresh()->world_position);
    }

    public function test_update_prop_position_persists_without_geometric_restrictions(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updatePropPosition', $prop->id, 999.0, 999.0)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $prop->fresh();
        $this->assertEquals(999.0, $fresh->world_position['x']);
        $this->assertEquals(999.0, $fresh->world_position['z']);
    }

    public function test_it_can_save_selected_slot_properties_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();
        $slot = $this->makeSlot($plaza, 'S1', $template->id);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'slot', $slot->id)
            ->set('selectedObjectForm.position.x', 2.5)
            ->set('selectedObjectForm.position.z', 3.5)
            ->set('selectedObjectForm.rotation.y', 45)
            ->set('selectedObjectForm.size.x', 3.0)
            ->set('selectedObjectForm.size.z', 2.8)
            ->call('saveSelectedObject');

        $fresh = $slot->fresh();
        $this->assertEquals(2.5, $fresh->world_position['x']);
        $this->assertEquals(3.5, $fresh->world_position['z']);
        $this->assertEquals(45.0, $fresh->rotation['y']);
        $this->assertEquals(3.0, $fresh->max_width);
        $this->assertEquals(2.8, $fresh->max_depth);
    }

    public function test_it_can_save_selected_prop_properties_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->set('selectedObjectForm.position.x', 7)
            ->set('selectedObjectForm.position.y', 1)
            ->set('selectedObjectForm.position.z', 9)
            ->set('selectedObjectForm.rotation.y', 90)
            ->set('selectedObjectForm.size.x', 4)
            ->set('selectedObjectForm.size.y', 2)
            ->set('selectedObjectForm.size.z', 3)
            ->set('selectedObjectForm.collisionEnabled', true)
            ->call('saveSelectedObject');

        $fresh = $prop->fresh();
        $this->assertEquals(7.0, $fresh->world_position['x']);
        $this->assertEquals(1.0, $fresh->world_position['y']);
        $this->assertEquals(9.0, $fresh->world_position['z']);
        $this->assertEquals(90.0, $fresh->rotation['y']);
        $this->assertSame(['x' => 2.0, 'y' => 2.0, 'z' => 3.0], $fresh->scaleVector());
        $this->assertTrue($fresh->collision_enabled);
    }

    public function test_locked_size_scales_the_other_axes_proportionally(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->call('toggleSizeLock')
            ->set('selectedObjectForm.size.x', 4)
            ->assertSet('selectedObjectForm.size.y', 2.0)
            ->assertSet('selectedObjectForm.size.z', 2.0);
    }

    public function test_unlocked_size_does_not_scale_the_other_axes(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->set('selectedObjectForm.size.x', 4)
            ->assertSet('selectedObjectForm.size.y', 1.0)
            ->assertSet('selectedObjectForm.size.z', 1.0);
    }

    public function test_it_can_save_navigable_bounds_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('boundsForm.minX', -80)
            ->set('boundsForm.maxX', 90)
            ->set('boundsForm.minZ', -70)
            ->set('boundsForm.maxZ', 95)
            ->call('saveSpatialSettings');

        $this->assertEquals(['minX' => -80.0, 'maxX' => 90.0, 'minZ' => -70.0, 'maxZ' => 95.0], $plaza->fresh()->navigable_bounds);
    }

    /**
     * Pedido del usuario: el punto de aparición se selecciona/edita como
     * cualquier otro objeto (mismo panel de Propiedades), no con un mini
     * formulario aparte.
     */
    public function test_it_can_save_the_spawn_point_from_the_properties_panel(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'spawn', -1)
            ->set('selectedObjectForm.position.x', 10)
            ->set('selectedObjectForm.position.y', 1)
            ->set('selectedObjectForm.position.z', 20)
            ->set('selectedObjectForm.rotation.y', 135)
            ->call('saveSelectedObject');

        $this->assertEquals(
            ['x' => 10.0, 'y' => 1.0, 'z' => 20.0, 'rotationY' => 135.0],
            $plaza->fresh()->spawn_point,
        );
    }

    public function test_dragging_the_spawn_marker_updates_its_x_and_z(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSpawnPosition', 12.5, -8.25);

        $fresh = $plaza->fresh()->spawn_point;
        $this->assertSame(12.5, $fresh['x']);
        $this->assertSame(-8.25, $fresh['z']);
    }

    public function test_it_can_add_a_new_prop_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Farol nuevo', 'slug' => 'farol-nuevo-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'lamp',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 3, 'status' => 'publicada',
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('newPropTemplateId', $template->id)
            ->call('addProp')
            ->assertSet('selectedObjectType', 'prop');

        $prop = $plaza->fresh()->props()->latest('id')->first();

        $this->assertNotNull($prop);
        $this->assertSame($template->id, $prop->object_template_id);
        $this->assertEquals(['x' => 0.0, 'y' => 0.0, 'z' => 0.0], $prop->world_position);
    }

    public function test_it_ignores_updates_for_objects_that_do_not_belong_to_this_plaza(): void
    {
        $plaza = $this->makePlaza();
        $otherPlaza = $this->makePlaza();
        $slot = $this->makeSlot($otherPlaza, 'S1');
        $originalPosition = $slot->world_position;

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 1.0, 1.0);

        $this->assertSame($originalPosition, $slot->fresh()->world_position);
    }

    public function test_it_ignores_updates_for_props_that_do_not_belong_to_this_plaza(): void
    {
        $plaza = $this->makePlaza();
        $otherPlaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $otherPlaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updatePropPosition', $prop->id, 1.0, 1.0);

        $this->assertSame(['x' => 0, 'y' => 0, 'z' => 0], $prop->fresh()->world_position);
    }
}
