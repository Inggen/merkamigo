<?php

namespace Tests\Feature\Immersive;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\ListImmersiveObjectTemplates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pedido del usuario: "Agrega aqui una opción para duplicar un objeto" en
 * el Catálogo De Objetos 3D. Mismo patrón que
 * `ImmersiveExperience::duplicate()`/`ImmersiveExperiencesTable`.
 */
class ImmersiveObjectTemplateDuplicationTest extends TestCase
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

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Farol colonial',
            'slug' => 'farol-colonial',
            'category' => 'monumento',
            'status' => 'publicada',
            'max_width' => 2,
            'max_depth' => 2,
            'max_height' => 4,
            'max_boxes' => 20,
            'model_definition' => ['version' => 1, 'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'iron', 'rotationY' => 0, 'emissive' => '#ffd873'],
            ]],
        ]);
    }

    public function test_duplicate_clones_the_templates_own_fields_as_a_draft(): void
    {
        $template = $this->makeTemplate();

        $copy = $template->duplicate();

        $this->assertNotSame($template->id, $copy->id);
        $this->assertSame('Farol colonial (copia)', $copy->name);
        $this->assertSame('farol-colonial-copia', $copy->slug);
        $this->assertSame('borrador', $copy->status);
        $this->assertSame('monumento', $copy->category);
        $this->assertSame($template->model_definition, $copy->model_definition);

        // El original no se toca.
        $this->assertSame('publicada', $template->fresh()->status);
    }

    public function test_duplicating_twice_produces_unique_slugs(): void
    {
        $template = $this->makeTemplate();

        $first = $template->duplicate();
        $second = $template->duplicate();

        $this->assertSame('farol-colonial-copia', $first->slug);
        $this->assertSame('farol-colonial-copia-2', $second->slug);
    }

    public function test_the_duplicate_action_is_available_from_the_catalog_list(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $template = $this->makeTemplate();

        Livewire::test(ListImmersiveObjectTemplates::class)
            ->callTableAction('duplicate', $template)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('immersive_object_templates', ['slug' => 'farol-colonial-copia']);
    }
}
