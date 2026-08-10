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
 * Pedido del usuario: deshacer/rehacer cambios de cajas en el editor de
 * objetos, con dos botones junto a "Editor de Objeto (3D)".
 */
class ObjectTemplateSpatialEditorUndoRedoTest extends TestCase
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

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        return $admin;
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Plantilla de prueba',
            'slug' => 'plantilla-'.uniqid(),
            'category' => 'construccion',
            'max_width' => 4.0,
            'max_depth' => 4.0,
            'max_height' => 3.0,
            'status' => 'publicada',
            'model_definition' => ['version' => 1, 'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'stone', 'rotationY' => 0, 'collidable' => false],
            ]],
        ]);
    }

    public function test_undo_and_redo_are_disabled_with_no_history(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);

        $this->assertFalse($component->instance()->canUndo());
        $this->assertFalse($component->instance()->canRedo());
    }

    public function test_undo_restores_the_box_count_before_adding_one(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);
        $this->assertCount(1, $component->get('sceneData')['boxes']);

        $component->call('addBox');
        $this->assertCount(2, $component->get('sceneData')['boxes']);
        $this->assertTrue($component->instance()->canUndo());

        $component->call('undo');
        $this->assertCount(1, $component->get('sceneData')['boxes']);
        $this->assertFalse($component->instance()->canUndo());
        $this->assertTrue($component->instance()->canRedo());

        $this->assertCount(1, $template->fresh()->model_definition['boxes']);
    }

    public function test_redo_reapplies_the_undone_change(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);

        $component->call('addBox');
        $component->call('undo');
        $this->assertCount(1, $component->get('sceneData')['boxes']);

        $component->call('redo');
        $this->assertCount(2, $component->get('sceneData')['boxes']);
        $this->assertTrue($component->instance()->canUndo());
        $this->assertFalse($component->instance()->canRedo());

        $this->assertCount(2, $template->fresh()->model_definition['boxes']);
    }

    /**
     * Un cambio nuevo después de deshacer invalida cualquier "rehacer"
     * pendiente — no tendría sentido rehacer algo que ya no es el punto de
     * partida actual.
     */
    public function test_a_new_edit_after_undo_clears_the_redo_stack(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);

        $component->call('addBox');
        $component->call('undo');
        $this->assertTrue($component->instance()->canRedo());

        $component->call('addBox');
        $this->assertFalse($component->instance()->canRedo());
    }

    public function test_undo_is_a_no_op_when_there_is_nothing_to_undo(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);

        $component->call('undo');
        $this->assertCount(1, $component->get('sceneData')['boxes']);
    }

    public function test_multiple_undos_walk_back_through_history_in_order(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);

        $component->call('addBox'); // 2 boxes
        $component->call('addBox'); // 3 boxes
        $component->call('addBox'); // 4 boxes
        $this->assertCount(4, $component->get('sceneData')['boxes']);

        $component->call('undo');
        $this->assertCount(3, $component->get('sceneData')['boxes']);

        $component->call('undo');
        $this->assertCount(2, $component->get('sceneData')['boxes']);

        $component->call('undo');
        $this->assertCount(1, $component->get('sceneData')['boxes']);
        $this->assertFalse($component->instance()->canUndo());
    }
}
