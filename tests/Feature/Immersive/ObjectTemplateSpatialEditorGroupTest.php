<?php

namespace Tests\Feature\Immersive;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Livewire\ObjectTemplateSpatialEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pedido del usuario: agrupar cajas del editor de objeto para moverlas/
 * rotarlas/escalarlas juntas con el gizmo, renombrar el grupo y bloquear/
 * desbloquear todos sus miembros a la vez.
 */
class ObjectTemplateSpatialEditorGroupTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        setPermissionsTeamId($previousTeamId);

        $this->actingAs($admin);

        return $admin;
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Plantilla de prueba',
            'slug' => 'plantilla-'.uniqid(),
            'category' => 'construccion',
            'max_width' => 20.0,
            'max_depth' => 20.0,
            'max_height' => 20.0,
            'status' => 'publicada',
            'model_definition' => ['version' => 1, 'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'stone', 'rotationY' => 0, 'collidable' => false],
                ['x' => 2, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
                ['x' => 4, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'brick', 'rotationY' => 0, 'collidable' => false],
            ]],
        ]);
    }

    /**
     * Pedido del usuario: el checkbox de cada caja manda su estado
     * explícito (`$event.target.checked`), no un "alternar" contra el
     * último snapshot sincronizado — evita la condición de carrera que
     * perdía marcas cuando se marcaban varias cajas rápido (bug reportado).
     */
    public function test_setting_the_grouping_selection_for_multiple_boxes_accumulates(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('setGroupingSelection', 0, true)
            ->call('setGroupingSelection', 1, true)
            ->call('setGroupingSelection', 2, true);

        $component->assertSet('selectedForGrouping', [0, 1, 2]);
    }

    public function test_setting_the_grouping_selection_to_false_removes_it(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('setGroupingSelection', 0, true)
            ->call('setGroupingSelection', 1, true)
            ->call('setGroupingSelection', 0, false);

        $component->assertSet('selectedForGrouping', [1]);
    }

    public function test_setting_the_same_selection_twice_does_not_duplicate_it(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('setGroupingSelection', 0, true)
            ->call('setGroupingSelection', 0, true);

        $component->assertSet('selectedForGrouping', [0]);
    }

    public function test_grouping_fewer_than_two_boxes_is_rejected(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0])
            ->call('createGroup');

        $this->assertArrayNotHasKey('groups', $template->fresh()->model_definition);
    }

    public function test_creating_a_group_assigns_group_id_and_selects_it(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $definition = $template->fresh()->model_definition;
        $this->assertCount(1, $definition['groups']);
        $groupId = $definition['groups'][0]['id'];
        $this->assertSame('Grupo 1', $definition['groups'][0]['name']);
        $this->assertSame($groupId, $definition['boxes'][0]['groupId']);
        $this->assertSame($groupId, $definition['boxes'][1]['groupId']);
        $this->assertArrayNotHasKey('groupId', $definition['boxes'][2]);

        $component->assertSet('selectedGroupId', $groupId);
        $component->assertSet('selectedForGrouping', []);
    }

    /**
     * `selectedForGrouping` se llena con checkboxes HTML ligados vía
     * `wire:model` (`value="{{ $box['index'] }}"`) — sus valores llegan
     * como string, no como int. `createGroup()` debe seguir encontrando
     * las cajas correctas.
     */
    public function test_creating_a_group_works_with_string_indices_from_checkboxes(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', ['0', '1'])
            ->call('createGroup');

        $definition = $template->fresh()->model_definition;
        $this->assertCount(1, $definition['groups']);
        $groupId = $definition['groups'][0]['id'];
        $this->assertSame($groupId, $definition['boxes'][0]['groupId']);
        $this->assertSame($groupId, $definition['boxes'][1]['groupId']);
    }

    public function test_selecting_a_box_clears_the_selected_group(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $component->call('selectBox', 2);

        $component->assertSet('selectedGroupId', null);
        $component->assertSet('selectedBoxIndex', 2);
    }

    public function test_selecting_a_group_clears_the_selected_box(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('selectBox', 2)
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $component->assertSet('selectedBoxIndex', null);
    }

    public function test_renaming_the_selected_group(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup')
            ->set('selectedGroupForm.name', 'Techo')
            ->call('renameSelectedGroup');

        $this->assertSame('Techo', $template->fresh()->model_definition['groups'][0]['name']);
    }

    public function test_renaming_to_an_empty_name_is_rejected(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup')
            ->set('selectedGroupForm.name', '   ')
            ->call('renameSelectedGroup');

        $this->assertSame('Grupo 1', $template->fresh()->model_definition['groups'][0]['name']);
    }

    public function test_ungrouping_clears_the_group_id_but_keeps_the_boxes(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $component->call('ungroupSelected');

        $definition = $template->fresh()->model_definition;
        $this->assertSame([], $definition['groups']);
        $this->assertCount(3, $definition['boxes']);
        $this->assertArrayNotHasKey('groupId', $definition['boxes'][0]);
        $this->assertArrayNotHasKey('groupId', $definition['boxes'][1]);

        $component->assertSet('selectedGroupId', null);
    }

    public function test_toggle_group_lock_locks_all_members_when_none_are_locked(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];
        $component->call('toggleGroupLock', $groupId);

        $definition = $template->fresh()->model_definition;
        $this->assertTrue($definition['boxes'][0]['locked']);
        $this->assertTrue($definition['boxes'][1]['locked']);
        $this->assertFalse($definition['boxes'][2]['locked'] ?? false);
    }

    /**
     * Con un estado mixto (una caja bloqueada, otra no), el candado del
     * grupo no exige que TODAS estén ya bloqueadas para poder actuar —
     * mueve hacia "todas bloqueadas" (la próxima pulsación recién ahí
     * desbloquea todo). Es el sentido más seguro: nunca destruye un
     * candado individual como efecto secundario de tocar el del grupo.
     */
    public function test_toggle_group_lock_from_a_mixed_state_locks_the_rest_first(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];

        // Solo una caja del grupo bloqueada a mano...
        $component->call('toggleBoxLock', 0);
        // ...la primera pulsación del candado del grupo bloquea la que
        // faltaba (mueve hacia "todas bloqueadas")...
        $component->call('toggleGroupLock', $groupId);

        $definition = $template->fresh()->model_definition;
        $this->assertTrue($definition['boxes'][0]['locked']);
        $this->assertTrue($definition['boxes'][1]['locked']);

        // ...y solo estando ya todas bloqueadas, la siguiente pulsación
        // desbloquea todo.
        $component->call('toggleGroupLock', $groupId);

        $definition = $template->fresh()->model_definition;
        $this->assertFalse($definition['boxes'][0]['locked']);
        $this->assertFalse($definition['boxes'][1]['locked']);
    }

    public function test_toggle_group_lock_does_not_affect_undo_history(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];
        $undoableBefore = $component->instance()->canUndo();

        $component->call('toggleGroupLock', $groupId);

        $this->assertSame($undoableBefore, $component->instance()->canUndo());
    }

    public function test_gizmo_updates_all_group_members_in_a_single_call(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];

        $component->call('updateGroupBoxes', $groupId, [
            ['index' => 0, 'x' => 5.0, 'y' => 1.0, 'z' => 5.0, 'rotationX' => 0.0, 'rotationY' => 0.5, 'rotationZ' => 0.0],
            ['index' => 1, 'x' => 7.0, 'y' => 1.0, 'z' => 5.0, 'rotationX' => 0.0, 'rotationY' => 0.5, 'rotationZ' => 0.0],
        ]);

        $definition = $template->fresh()->model_definition;
        $this->assertSame(5.0, (float) $definition['boxes'][0]['x']);
        $this->assertSame(7.0, (float) $definition['boxes'][1]['x']);
        // Un solo `undo()` revierte el movimiento del grupo COMPLETO (las
        // 2 cajas) — un solo registro en el historial para todo el grupo,
        // no uno por caja.
        $component->call('undo');
        $this->assertSame(0.0, (float) $template->fresh()->model_definition['boxes'][0]['x']);
        $this->assertSame(2.0, (float) $template->fresh()->model_definition['boxes'][1]['x']);
    }

    public function test_gizmo_scale_multiplies_dimensions_and_offset_for_every_member(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];

        $component->call('updateGroupBoxes', $groupId, [
            ['index' => 0, 'x' => -1.0, 'y' => 0.5, 'z' => 0.0, 'rotationX' => 0.0, 'rotationY' => 0.0, 'rotationZ' => 0.0, 'w' => 2.0, 'h' => 2.0, 'd' => 2.0],
            ['index' => 1, 'x' => 1.0, 'y' => 0.5, 'z' => 0.0, 'rotationX' => 0.0, 'rotationY' => 0.0, 'rotationZ' => 0.0, 'w' => 2.0, 'h' => 2.0, 'd' => 2.0],
        ]);

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame(2.0, (float) $box['w']);
        $this->assertSame(2.0, (float) $box['h']);
        $this->assertSame(2.0, (float) $box['d']);
    }

    public function test_a_group_transform_that_exceeds_the_max_size_is_rejected(): void
    {
        $this->makeAdmin();
        // max_width/depth/height quedan en 20 (default de makeTemplate) —
        // el grupo se prueba de mover a un spread de 100m, muy por encima,
        // sin tocar el límite y así aislar que el rechazo viene del
        // MOVIMIENTO del grupo, no de un layout inicial ya al límite.
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];

        $component->call('updateGroupBoxes', $groupId, [
            ['index' => 0, 'x' => -50.0, 'y' => 0.5, 'z' => 0.0, 'rotationX' => 0.0, 'rotationY' => 0.0, 'rotationZ' => 0.0],
            ['index' => 1, 'x' => 50.0, 'y' => 0.5, 'z' => 0.0, 'rotationX' => 0.0, 'rotationY' => 0.0, 'rotationZ' => 0.0],
        ])->assertDispatched('object-editor-reject');

        $this->assertSame(0.0, (float) $template->fresh()->model_definition['boxes'][0]['x']);
    }

    public function test_deleting_the_last_box_of_a_group_prunes_the_empty_group(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [1, 2])
            ->call('createGroup');

        // Ahora deja solo 1 caja en el grupo (índice 2, tras borrar la 1).
        $component->call('selectBox', 1)->call('deleteSelectedBox');
        $definitionAfterFirstDelete = $template->fresh()->model_definition;
        $this->assertCount(1, $definitionAfterFirstDelete['groups']);

        // Al borrar la última caja del grupo, el grupo debe desaparecer.
        $remainingGroupedIndex = collect($definitionAfterFirstDelete['boxes'])
            ->search(fn (array $box): bool => filled($box['groupId'] ?? null));
        $component->call('selectBox', $remainingGroupedIndex)->call('deleteSelectedBox');

        $this->assertSame([], $template->fresh()->model_definition['groups']);
    }

    public function test_duplicating_a_grouped_box_does_not_join_the_group(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $component->call('selectBox', 0)->call('duplicateSelectedBox');

        $definition = $template->fresh()->model_definition;
        $this->assertArrayNotHasKey('groupId', $definition['boxes'][3]);
    }

    /**
     * Pedido del usuario: un botón para duplicar toda una agrupación —
     * copia todas sus cajas dentro de un grupo NUEVO, dejando el original
     * intacto.
     */
    public function test_duplicating_a_group_copies_all_its_boxes_into_a_new_group(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $originalGroupId = $template->fresh()->model_definition['groups'][0]['id'];

        $component->call('duplicateSelectedGroup');

        $definition = $template->fresh()->model_definition;
        $this->assertCount(5, $definition['boxes']);
        $this->assertCount(2, $definition['groups']);

        $newGroup = collect($definition['groups'])->firstWhere('id', '!=', $originalGroupId);
        $this->assertNotNull($newGroup);
        $this->assertSame('Grupo 1 (copia)', $newGroup['name']);

        $newMembers = collect($definition['boxes'])->filter(fn (array $box): bool => ($box['groupId'] ?? null) === $newGroup['id']);
        $this->assertCount(2, $newMembers);

        // El original sigue con sus 2 cajas propias, sin tocar.
        $originalMembers = collect($definition['boxes'])->filter(fn (array $box): bool => ($box['groupId'] ?? null) === $originalGroupId);
        $this->assertCount(2, $originalMembers);

        $component->assertSet('selectedGroupId', $newGroup['id']);
    }

    public function test_duplicating_a_group_offsets_the_copies_and_unlocks_them(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('selectedForGrouping', [0, 1])
            ->call('createGroup');

        $groupId = $template->fresh()->model_definition['groups'][0]['id'];
        $component->call('toggleGroupLock', $groupId);

        $component->call('duplicateSelectedGroup');

        $definition = $template->fresh()->model_definition;
        $newGroup = collect($definition['groups'])->firstWhere('id', '!=', $groupId);
        $copies = collect($definition['boxes'])->filter(fn (array $box): bool => ($box['groupId'] ?? null) === $newGroup['id'])->values();

        $this->assertSame(0.5, (float) $copies[0]['x']);
        $this->assertSame(2.5, (float) $copies[1]['x']);
        $this->assertFalse($copies[0]['locked']);
        $this->assertFalse($copies[1]['locked']);
    }
}
