<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Livewire\PlazaSpatialEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pedido del usuario: un elemento nuevo queda en "borrador" (solo visible
 * con `?preview=1`) y antes solo se podía confirmar desde el recurso
 * Filament "Elementos de plaza", fila por fila — este botón lo permite
 * directamente desde el editor espacial.
 */
class PlazaSpatialEditorConfirmPropTest extends TestCase
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

    private function makePropTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Farol', 'slug' => 'farol-'.uniqid(), 'category' => 'construccion', 'builder_key' => 'lamp',
            'max_width' => 1, 'max_depth' => 1, 'max_height' => 3, 'status' => 'publicada',
        ]);
    }

    public function test_confirm_prop_changes_a_draft_to_confirmado(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 1, 'y' => 0, 'z' => 1],
            'status' => 'borrador',
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('confirmProp', $prop->id);

        $this->assertSame('confirmado', $prop->fresh()->status);
    }

    public function test_confirm_prop_updates_the_selected_object_form_when_that_prop_is_selected(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 1, 'y' => 0, 'z' => 1],
            'status' => 'borrador',
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('selectObject', 'prop', $prop->id);

        $this->assertSame('borrador', $component->get('selectedObjectForm')['status']);

        $component->call('confirmProp', $prop->id);

        $this->assertSame('confirmado', $component->get('selectedObjectForm')['status']);
    }

    public function test_confirm_prop_is_undoable(): void
    {
        $plaza = $this->makePlaza();
        $template = $this->makePropTemplate();
        $prop = $plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 1, 'y' => 0, 'z' => 1],
            'status' => 'borrador',
        ]);

        $component = Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('confirmProp', $prop->id);

        $this->assertSame('confirmado', $prop->fresh()->status);
        $this->assertTrue($component->instance()->canUndo());

        $component->call('undo');

        $this->assertSame('borrador', $prop->fresh()->status);
    }

    public function test_confirm_prop_does_nothing_for_a_prop_from_another_plaza(): void
    {
        $plaza = $this->makePlaza();
        $otherPlaza = $this->makePlaza();
        $template = $this->makePropTemplate();
        $foreignProp = $otherPlaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => ['x' => 1, 'y' => 0, 'z' => 1],
            'status' => 'borrador',
        ]);

        Livewire::test(PlazaSpatialEditor::class, ['plaza' => $plaza])
            ->call('confirmProp', $foreignProp->id);

        $this->assertSame('borrador', $foreignProp->fresh()->status);
    }
}
