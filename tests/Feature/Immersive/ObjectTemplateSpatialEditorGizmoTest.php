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
 * Pedido del usuario: el editor de cajas de un objeto debe tener las mismas
 * propiedades que el editor de plaza (gizmo Mover/Rotar/Escalar, tiling de
 * textura, candado), pero sin límite de rotación en X/Z.
 */
class ObjectTemplateSpatialEditorGizmoTest extends TestCase
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
            'max_width' => 4.0,
            'max_depth' => 4.0,
            'max_height' => 3.0,
            'status' => 'publicada',
            'model_definition' => ['version' => 1, 'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'stone', 'rotationY' => 0, 'collidable' => false],
            ]],
        ]);
    }

    public function test_gizmo_translate_moves_the_box_on_all_three_axes(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('updateBoxPosition', 0, 1.5, 0.75, -1.25);

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame(1.5, $box['x']);
        $this->assertSame(0.75, $box['y']);
        $this->assertSame(-1.25, $box['z']);
    }

    /**
     * A diferencia de `PlazaSpatialEditor` (que fuerza rotation.x/z a 0 al
     * guardar), las cajas rotan libre en los 3 ejes.
     */
    public function test_gizmo_rotate_sets_rotation_on_all_three_axes_without_restriction(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('updateBoxRotation', 0, 0.4, 0.1, 0.9);

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame(0.4, $box['rotationX']);
        $this->assertSame(0.1, $box['rotationY']);
        $this->assertSame(0.9, $box['rotationZ']);
    }

    public function test_gizmo_scale_multiplies_the_box_dimensions(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('updateBoxScale', 0, 2.0, 1.5, 0.5);

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertEqualsWithDelta(2.0, $box['w'], 0.0001);
        $this->assertEqualsWithDelta(1.5, $box['h'], 0.0001);
        $this->assertEqualsWithDelta(0.5, $box['d'], 0.0001);
    }

    public function test_a_transform_that_would_exceed_the_max_size_is_rejected(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('updateBoxScale', 0, 10.0, 1.0, 1.0)
            ->assertDispatched('object-editor-reject');

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame(1, $box['w']);
    }

    public function test_toggle_box_lock_flips_the_locked_flag_and_does_not_affect_undo(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);
        $component->call('toggleBoxLock', 0);

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertTrue($box['locked']);
        $this->assertFalse($component->instance()->canUndo());

        $component->call('toggleBoxLock', 0);
        $this->assertFalse($template->fresh()->model_definition['boxes'][0]['locked']);
    }

    public function test_saving_the_selected_box_persists_tiling(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('selectBox', 0)
            ->set('selectedBoxForm.tiling.u', 3)
            ->set('selectedBoxForm.tiling.v', 2)
            ->call('saveSelectedBox');

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame(3.0, (float) $box['tiling']['u']);
        $this->assertSame(2.0, (float) $box['tiling']['v']);
    }

    public function test_saving_the_selected_box_persists_the_glow_color_only_when_enabled(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('selectBox', 0)
            ->set('selectedBoxForm.glowEnabled', true)
            ->set('selectedBoxForm.glowColor', '#ffaa00')
            ->call('saveSelectedBox');

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertSame('#ffaa00', $box['emissive']);
    }

    public function test_disabling_the_glow_clears_the_emissive_color(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();
        $template->update(['model_definition' => ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'stone', 'rotationY' => 0, 'emissive' => '#ffaa00'],
        ]]]);

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('selectBox', 0)
            ->assertSet('selectedBoxForm.glowEnabled', true)
            ->set('selectedBoxForm.glowEnabled', false)
            ->call('saveSelectedBox');

        $box = $template->fresh()->model_definition['boxes'][0];
        $this->assertNull($box['emissive']);
    }

    public function test_duplicating_a_locked_box_creates_an_unlocked_copy(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        $component = Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template]);
        $component->call('toggleBoxLock', 0);
        $component->call('selectBox', 0);
        $component->call('duplicateSelectedBox');

        $boxes = $template->fresh()->model_definition['boxes'];
        $this->assertTrue($boxes[0]['locked']);
        $this->assertFalse($boxes[1]['locked']);
    }
}
