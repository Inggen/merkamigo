<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandSlot;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use App\Livewire\PlazaSpatialEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_empty_properties_panel_shows_add_object_call_to_action(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->assertSee('Todavía no hay un objeto seleccionado.')
            ->assertSee('Agregar objeto')
            ->assertSee(ImmersiveObjectTemplateResource::getUrl('create'), false);
    }

    public function test_it_can_lock_and_unlock_an_object_for_the_3d_viewer(): void
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

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('toggleObjectLock', 'prop', $prop->id);

        $locked = collect($component->get('sceneData')['objects'])
            ->first(fn (array $object): bool => $object['type'] === 'prop' && $object['id'] === $prop->id);

        $this->assertNotNull($locked);
        $this->assertTrue($locked['locked']);

        $component->call('toggleObjectLock', 'prop', $prop->id);

        $unlocked = collect($component->get('sceneData')['objects'])
            ->first(fn (array $object): bool => $object['type'] === 'prop' && $object['id'] === $prop->id);

        $this->assertNotNull($unlocked);
        $this->assertFalse($unlocked['locked']);
    }

    /**
     * Bug reportado por el usuario: el candado siempre volvía a aparecer
     * abierto al recargar la página, porque antes vivía solo en una
     * propiedad Livewire en memoria (`$lockedObjectKeys`), nunca en BD. La
     * prueba de arriba solo comprobaba el mismo componente en memoria — esta
     * simula el "reload" real: releer el modelo directo de BD, y montar un
     * componente Livewire NUEVO (sin ningún estado compartido con el que
     * hizo el toggle), igual que pasa al refrescar el navegador.
     */
    public function test_locking_an_object_persists_across_a_fresh_page_load(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();
        $slot = $this->makeSlot($plaza, 'S1', $template->id);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('toggleObjectLock', 'slot', $slot->id)
            ->call('toggleObjectLock', 'prop', $prop->id)
            ->call('toggleObjectLock', 'spawn', -1);

        $this->assertTrue($slot->fresh()->locked);
        $this->assertTrue($prop->fresh()->locked);
        $this->assertTrue($plaza->fresh()->spawn_point['locked']);

        $reloaded = collect(
            Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza->fresh()])->get('sceneData')['objects']
        );

        $this->assertTrue($reloaded->firstWhere(fn (array $object) => $object['type'] === 'slot')['locked']);
        $this->assertTrue($reloaded->firstWhere(fn (array $object) => $object['type'] === 'prop')['locked']);
        $this->assertTrue($reloaded->firstWhere(fn (array $object) => $object['type'] === 'spawn')['locked']);
    }

    /**
     * Guardar la posición/rotación del punto de aparición no debe borrar
     * su candado — todas las escrituras a `spawn_point` reconstruyen el
     * JSON completo desde cero, así que si alguna olvida preservar
     * `locked` el bloqueo desaparece silenciosamente en el siguiente
     * movimiento.
     */
    public function test_moving_the_locked_spawn_point_keeps_it_locked(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('toggleObjectLock', 'spawn', -1)
            ->call('updateSpawnPosition', 3.0, 0.0, 4.0);

        $this->assertTrue($plaza->fresh()->spawn_point['locked']);

        $component->call('updateSpawnRotation', 90.0);

        $this->assertTrue($plaza->fresh()->spawn_point['locked']);
    }

    public function test_update_slot_position_persists_a_valid_position(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 3.0, 0.0, 4.0)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $slot->fresh();
        $this->assertEquals(3.0, $fresh->world_position['x']);
        $this->assertEquals(4.0, $fresh->world_position['z']);
    }

    /**
     * Gizmo de rotación (círculo de eje Y, `TransformControls` en modo
     * `rotate`): solo se persiste `y`, X/Z quedan como estaban.
     */
    public function test_update_slot_rotation_persists_the_y_axis(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotRotation', $slot->id, 45.0)
            ->assertNotDispatched('spatial-editor-reject');

        $this->assertEquals(45.0, $slot->fresh()->rotation['y']);
    }

    /**
     * Pedido del usuario: el admin necesita poder mover un stand libre por
     * la plaza sin que el polígono de su zona se lo impida — el check de
     * "la huella sale del polígono de su zona" se quitó de
     * `StandSlot::validateAgainstZoneTemplateAndNeighbors()`.
     */
    public function test_update_slot_position_persists_without_the_zone_polygon_restriction(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 999.0, 0.0, 999.0)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $slot->fresh();
        $this->assertSame(999.0, (float) $fresh->world_position['x']);
        $this->assertSame(999.0, (float) $fresh->world_position['z']);
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
            ->call('updatePropPosition', $prop->id, 999.0, 0.0, 999.0)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $prop->fresh();
        $this->assertEquals(999.0, $fresh->world_position['x']);
        $this->assertEquals(999.0, $fresh->world_position['z']);
    }

    public function test_update_prop_rotation_persists_the_y_axis(): void
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
            ->call('updatePropRotation', $prop->id, 90.0)
            ->assertNotDispatched('spatial-editor-reject');

        $this->assertEquals(90.0, $prop->fresh()->rotation['y']);
    }

    /**
     * Gizmo de escala (`TransformControls` en modo `scale`) — solo props,
     * persiste en `scale_vector` (mismo campo que ya guarda el panel de
     * Propiedades vía "Guardar props").
     */
    public function test_update_prop_scale_persists_the_scale_vector(): void
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
            ->call('updatePropScale', $prop->id, 2.0, 1.5, 0.5)
            ->assertNotDispatched('spatial-editor-reject');

        $fresh = $prop->fresh();
        $this->assertEquals(2.0, $fresh->scale_vector['x']);
        $this->assertEquals(1.5, $fresh->scale_vector['y']);
        $this->assertEquals(0.5, $fresh->scale_vector['z']);
    }

    public function test_update_prop_scale_clamps_to_the_minimum_dimension(): void
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
            ->call('updatePropScale', $prop->id, -1.0, 0.0, 1.0);

        $fresh = $prop->fresh();
        $this->assertEquals(0.001, $fresh->scale_vector['x']);
        $this->assertEquals(0.001, $fresh->scale_vector['y']);
        $this->assertEquals(1.0, $fresh->scale_vector['z']);
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

    /**
     * Tiling de textura por instancia (Fase 4): se guarda vía el mismo
     * panel/botón "Guardar props", en `texture_tiling` — nunca en la
     * plantilla compartida.
     */
    public function test_saving_a_prop_persists_its_texture_tiling(): void
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
            ->call('toggleTilingLock', $prop->id)
            ->set('selectedObjectForm.tiling.u', 3.5)
            ->set('selectedObjectForm.tiling.v', 2.0)
            ->call('saveSelectedObject');

        $fresh = $prop->fresh();
        $this->assertEquals(['u' => 3.5, 'v' => 2.0], $fresh->textureTiling());
    }

    /**
     * Pedido del usuario: el tiling queda bloqueado (cerrado) por defecto —
     * cambiarlo sin desbloquear antes no debe persistir nada, para no
     * perder el que trae el objeto desde que fue creado.
     */
    public function test_saving_a_prop_with_tiling_locked_does_not_change_the_stored_tiling(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'texture_tiling' => ['u' => 1.0, 'v' => 1.0],
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->set('selectedObjectForm.tiling.u', 9.0)
            ->set('selectedObjectForm.tiling.v', 9.0)
            ->call('saveSelectedObject');

        $this->assertEquals(['u' => 1.0, 'v' => 1.0], $prop->fresh()->textureTiling());
    }

    public function test_new_props_start_with_tiling_locked(): void
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

        $this->assertTrue($prop->fresh()->texture_tiling_locked);
    }

    public function test_toggle_tiling_lock_flips_the_flag_and_persists(): void
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
            ->call('toggleTilingLock', $prop->id);

        $this->assertFalse($prop->fresh()->texture_tiling_locked);
    }

    /**
     * Pedido del usuario: botón junto al de info para restaurar el tiling
     * a su valor por defecto (1×1) sin tener que borrar los campos a mano.
     */
    public function test_reset_selected_tiling_sets_the_form_back_to_one_by_one(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'texture_tiling' => ['u' => 1.0, 'v' => 1.0],
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->call('toggleTilingLock', $prop->id)
            ->set('selectedObjectForm.tiling.u', 5.0)
            ->set('selectedObjectForm.tiling.v', 7.0)
            ->call('resetSelectedTiling');

        $component->assertSet('selectedObjectForm.tiling.u', 1.0);
        $component->assertSet('selectedObjectForm.tiling.v', 1.0);
        $component->assertDispatched('spatial-editor-tiling-preview');

        // Solo toca el formulario en pantalla — sigue haciendo falta
        // "Guardar props" para persistirlo.
        $this->assertEquals(['u' => 1.0, 'v' => 1.0], $prop->fresh()->textureTiling());
    }

    public function test_reset_selected_tiling_does_nothing_while_locked(): void
    {
        $plaza = $this->makePlaza();
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Banca', 'slug' => 'banca-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'bench',
            'max_width' => 2, 'max_depth' => 1, 'max_height' => 1, 'status' => 'publicada',
        ]);
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'texture_tiling' => ['u' => 3.0, 'v' => 3.0],
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id)
            ->call('resetSelectedTiling');

        $component->assertSet('selectedObjectForm.tiling.u', 3.0);
        $component->assertSet('selectedObjectForm.tiling.v', 3.0);
        $component->assertNotDispatched('spatial-editor-tiling-preview');
    }

    public function test_a_prop_without_a_chosen_tiling_defaults_to_one_by_one(): void
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

        $this->assertEquals(['u' => 1.0, 'v' => 1.0], $prop->textureTiling());

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza]);
        $scene = collect($component->get('sceneData')['objects'])
            ->first(fn (array $object): bool => $object['type'] === 'prop' && $object['id'] === $prop->id);

        $this->assertEquals(['u' => 1.0, 'v' => 1.0], $scene['tiling']);
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
     * Pedido del usuario: controlar la niebla (fog) de la escena por
     * plaza, como parte de la misma configuración espacial.
     */
    public function test_a_plaza_without_configured_fog_falls_back_to_the_engine_default(): void
    {
        $plaza = $this->makePlaza();

        $this->assertSame(
            ['enabled' => true, 'color' => '#b6d7f3', 'near' => 78.0, 'far' => 260.0],
            $plaza->fogSettings(),
        );
    }

    /**
     * Pedido del usuario: previsualizar en el editor espacial la imagen de
     * cielo que se sube desde "Editar Plaza" (Filament) — de solo lectura
     * acá, pero visible para que el admin no tenga que salir a comprobarla.
     */
    public function test_scene_data_exposes_the_sky_image_url_when_configured(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp']);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza]);

        $this->assertSame(
            Storage::disk('public')->url('immersive-plazas/cielo-360.webp'),
            $component->get('sceneData')['skyImageUrl'],
        );
    }

    public function test_scene_data_sky_image_url_is_null_without_one_configured(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza]);

        $this->assertNull($component->get('sceneData')['skyImageUrl']);
    }

    /**
     * Pedido del usuario: activar/desactivar el fondo equirectangular sin
     * borrar la imagen ya subida en "Editar Plaza".
     */
    public function test_disabling_the_sky_image_hides_it_without_deleting_the_upload(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp']);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->assertSet('skyImageEnabledForm', true)
            ->set('skyImageEnabledForm', false)
            ->call('saveSpatialSettings');

        $fresh = $plaza->fresh();
        $this->assertFalse($fresh->sky_image_enabled);
        $this->assertSame('immersive-plazas/cielo-360.webp', $fresh->sky_image_path);
        $this->assertNull($fresh->skyImageUrl());
        $this->assertNull($component->get('sceneData')['skyImageUrl']);
    }

    public function test_re_enabling_the_sky_image_shows_it_again(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp', 'sky_image_enabled' => false]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->assertSet('skyImageEnabledForm', false)
            ->set('skyImageEnabledForm', true)
            ->call('saveSpatialSettings');

        $this->assertTrue($plaza->fresh()->sky_image_enabled);
        $this->assertNotNull($component->get('sceneData')['skyImageUrl']);
    }

    public function test_undo_restores_the_previous_sky_image_enabled_state(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp']);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('skyImageEnabledForm', false)
            ->call('saveSpatialSettings');

        $component->call('undo');

        $this->assertTrue($plaza->fresh()->sky_image_enabled);
    }

    /**
     * Pedido del usuario: poder girar el fondo equirectangular hasta 360°
     * (ej. para alinear el horizonte de la imagen con la plaza).
     */
    public function test_saving_the_sky_rotation_persists_it_and_exposes_it_in_scene_data(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_image_path' => 'immersive-plazas/cielo-360.webp']);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->assertSet('skyRotationForm', 0.0)
            ->set('skyRotationForm', 220)
            ->call('saveSpatialSettings');

        $this->assertSame(220.0, $plaza->fresh()->sky_rotation);
        $this->assertSame(220.0, $component->get('sceneData')['skyRotation']);
    }

    /**
     * `fmod()` conserva el signo del dividendo en PHP — sin el ajuste
     * manual, un valor negativo (ej. escrito a mano en el input numérico)
     * se guardaría fuera de [0, 360).
     */
    public function test_the_sky_rotation_is_normalized_into_a_0_to_360_range(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('skyRotationForm', 400)
            ->call('saveSpatialSettings');
        $this->assertSame(40.0, $plaza->fresh()->sky_rotation);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza->fresh()])
            ->set('skyRotationForm', -30)
            ->call('saveSpatialSettings');
        $this->assertSame(330.0, $plaza->fresh()->sky_rotation);
    }

    public function test_undo_restores_the_previous_sky_rotation(): void
    {
        $plaza = $this->makePlaza();
        $plaza->update(['sky_rotation' => 90]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('skyRotationForm', 200)
            ->call('saveSpatialSettings');

        $component->call('undo');

        $this->assertSame(90.0, $plaza->fresh()->sky_rotation);
    }

    public function test_it_can_save_the_fog_settings_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('fogForm.enabled', false)
            ->set('fogForm.color', '#334455')
            ->set('fogForm.near', 20)
            ->set('fogForm.far', 120)
            ->call('saveSpatialSettings');

        $this->assertSame(
            ['enabled' => false, 'color' => '#334455', 'near' => 20.0, 'far' => 120.0],
            $plaza->fresh()->fogSettings(),
        );
    }

    public function test_undo_restores_the_previous_fog_settings(): void
    {
        $plaza = $this->makePlaza();

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->set('fogForm.enabled', false)
            ->set('fogForm.color', '#334455')
            ->set('fogForm.near', 20)
            ->set('fogForm.far', 120)
            ->call('saveSpatialSettings');

        $component->call('undo');

        $this->assertSame(
            ['enabled' => true, 'color' => '#b6d7f3', 'near' => 78.0, 'far' => 260.0],
            $plaza->fresh()->fogSettings(),
        );
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
            ['x' => 10.0, 'y' => 1.0, 'z' => 20.0, 'rotationY' => 135.0, 'locked' => false],
            $plaza->fresh()->spawn_point,
        );
    }

    public function test_dragging_the_spawn_marker_updates_its_x_and_z(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSpawnPosition', 12.5, 0.0, -8.25);

        $fresh = $plaza->fresh()->spawn_point;
        $this->assertSame(12.5, $fresh['x']);
        $this->assertSame(-8.25, $fresh['z']);
    }

    public function test_rotating_the_spawn_marker_updates_its_rotation_y(): void
    {
        $plaza = $this->makePlaza();

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSpawnRotation', 135.0);

        $this->assertEquals(135.0, $plaza->fresh()->spawn_point['rotationY']);
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

    /**
     * Pedido del usuario: poder duplicar un slot de stand desde el panel
     * de Propiedades, igual que ya existía para elementos (`duplicateProp`),
     * y que el duplicado quede a 2 metros de distancia del original (no
     * apilado encima, como sí hace `duplicateProp` con elementos sueltos).
     * El duplicado no debe heredar el estado "ocupada"/"reservado" del
     * original porque no hereda su `StandAssignment` (relación aparte).
     */
    public function test_it_can_duplicate_a_stand_slot_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makeTemplate();
        $slot = $this->makeSlot($plaza, 'S1', $template->id);
        $slot->update(['status' => 'ocupada']);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('duplicateSlot', $slot->id)
            ->assertSet('selectedObjectType', 'slot');

        $duplicate = StandSlot::query()->where('id', '!=', $slot->id)->where('stand_zone_id', $slot->stand_zone_id)->first();

        $this->assertNotNull($duplicate);
        $this->assertNotSame($slot->code, $duplicate->code);
        $this->assertSame($template->id, $duplicate->stand_template_id);
        $this->assertSame('disponible', $duplicate->status);
        $this->assertEquals($slot->world_position['x'] + $slot->max_width + 2.0, $duplicate->world_position['x']);
        $this->assertEquals($slot->world_position['z'], $duplicate->world_position['z']);

        $edgeGap = ($duplicate->world_position['x'] - $duplicate->max_width / 2)
            - ($slot->world_position['x'] + $slot->max_width / 2);
        $this->assertEqualsWithDelta(2.0, $edgeGap, 0.0001);
    }

    public function test_it_can_delete_a_stand_slot_from_the_editor_sidebar(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'slot', $slot->id)
            ->call('deleteSlot', $slot->id)
            ->assertSet('selectedObjectType', null);

        $this->assertNull(StandSlot::query()->find($slot->id));
    }

    public function test_it_ignores_updates_for_objects_that_do_not_belong_to_this_plaza(): void
    {
        $plaza = $this->makePlaza();
        $otherPlaza = $this->makePlaza();
        $slot = $this->makeSlot($otherPlaza, 'S1');
        $originalPosition = $slot->world_position;

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('updateSlotPosition', $slot->id, 1.0, 0.0, 1.0);

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
            ->call('updatePropPosition', $prop->id, 1.0, 0.0, 1.0);

        $this->assertSame(['x' => 0, 'y' => 0, 'z' => 0], $prop->fresh()->world_position);
    }
}
