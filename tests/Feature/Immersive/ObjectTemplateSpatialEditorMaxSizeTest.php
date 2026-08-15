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
 * Pedido del usuario: el tamaño máximo del objeto ya no se recalcula solo
 * al editar cajas — queda editable a mano como antes, y solo se ajusta al
 * contenido actual al pulsar el botón "Ajustar al contenido".
 */
class ObjectTemplateSpatialEditorMaxSizeTest extends TestCase
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

    public function test_adding_a_box_does_not_change_the_max_size(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('addBox');

        $template->refresh();
        $this->assertSame(4.0, (float) $template->max_width);
        $this->assertSame(4.0, (float) $template->max_depth);
        $this->assertSame(3.0, (float) $template->max_height);
    }

    public function test_the_max_size_fields_can_still_be_edited_by_hand(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->set('maxWidthForm', '2.5')
            ->set('maxDepthForm', '2.5')
            ->set('maxHeightForm', '2');

        $template->refresh();
        $this->assertSame(2.5, (float) $template->max_width);
        $this->assertSame(2.5, (float) $template->max_depth);
        $this->assertSame(2.0, (float) $template->max_height);
    }

    public function test_the_recalculate_button_fits_the_max_size_to_the_current_boxes(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(ObjectTemplateSpatialEditor::class, ['template' => $template])
            ->call('recalculateMaxSize');

        $template->refresh();
        $this->assertSame(1.0, (float) $template->max_width);
        $this->assertSame(1.0, (float) $template->max_depth);
        $this->assertSame(1.0, (float) $template->max_height);
    }
}
