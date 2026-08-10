<?php

namespace Tests\Feature\Immersive;

use App\Domain\Immersive\Contracts\GeneratesVoxelObjectDefinition;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelGenerationException;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\GenerateWithAi;
use App\Livewire\ImmersiveObjectTemplateAiGenerator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMM-020b del TODO inmersivo: panel de administración "Generar con IA".
 * `GenerateWithAi` (página de recurso) solo resuelve/autoriza el registro;
 * todo el flujo interactivo vive en el componente Livewire
 * `ImmersiveObjectTemplateAiGenerator`, embebido en esa página. Usa un doble
 * de `GeneratesVoxelObjectDefinition` en vez de `Http::fake` real: el
 * generador OpenAI en sí ya está cubierto por
 * `OpenAiVoxelObjectGeneratorTest`, aquí solo importa el comportamiento del
 * componente (estado, bitácora, validación, guardado) y la autorización de
 * la página que lo envuelve.
 */
class ObjectTemplateAiGeneratorPageTest extends TestCase
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

    private function makeTemplate(array $overrides = []): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create(array_merge([
            'name' => 'Plantilla de prueba',
            'slug' => 'plantilla-'.uniqid(),
            'category' => 'stand',
            'max_width' => 4.0,
            'max_depth' => 4.0,
            'max_height' => 3.0,
            'status' => 'publicada',
        ], $overrides));
    }

    private function fakeGenerator(array $definition): void
    {
        $this->app->bind(GeneratesVoxelObjectDefinition::class, fn () => new class($definition) implements GeneratesVoxelObjectDefinition
        {
            public function __construct(private readonly array $definition) {}

            public function generate(array $imagePaths, string $instructions, array $context = [], ?array $previousDefinition = null): array
            {
                return $this->definition;
            }
        });
    }

    /**
     * @param  array<string, mixed>|null  $captured  Se llena con
     *                                               ['imagePaths' => ..., 'context' => ...] en cuanto `generate()` se
     *                                               invoca — permite inspeccionar exactamente qué le llegó al contrato
     *                                               sin depender de `Http::fake` (eso ya lo cubre
     *                                               `OpenAiVoxelObjectGeneratorTest`).
     */
    private function fakeRecordingGenerator(array $definition, ?array &$captured): void
    {
        $this->app->bind(GeneratesVoxelObjectDefinition::class, function () use ($definition, &$captured) {
            return new class($definition, $captured) implements GeneratesVoxelObjectDefinition
            {
                private array $definition;

                private ?array $ref;

                public function __construct(array $definition, ?array &$captured)
                {
                    $this->definition = $definition;
                    $this->ref = &$captured;
                }

                public function generate(array $imagePaths, string $instructions, array $context = [], ?array $previousDefinition = null): array
                {
                    $this->ref = ['imagePaths' => $imagePaths, 'context' => $context];

                    return $this->definition;
                }
            };
        });
    }

    private function fakeFailingGenerator(): void
    {
        $this->app->bind(GeneratesVoxelObjectDefinition::class, fn () => new class implements GeneratesVoxelObjectDefinition
        {
            public function generate(array $imagePaths, string $instructions, array $context = [], ?array $previousDefinition = null): array
            {
                throw new VoxelGenerationException('la IA no respondió');
            }
        });
    }

    private function attachFakeImages($component): void
    {
        $component
            ->set('frontImage', UploadedFile::fake()->image('front.jpg'))
            ->set('sideImage', UploadedFile::fake()->image('side.jpg'))
            ->set('topImage', UploadedFile::fake()->image('top.jpg'));
    }

    public function test_a_non_admin_cannot_access_the_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $template = $this->makeTemplate();

        Livewire::test(GenerateWithAi::class, ['record' => $template->getRouteKey()])
            ->assertForbidden();
    }

    public function test_an_admin_can_access_the_page(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate();

        Livewire::test(GenerateWithAi::class, ['record' => $template->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_generate_updates_the_current_definition_and_the_log(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $definition = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]];
        $this->fakeGenerator($definition);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'una mesa pequeña');

        $this->attachFakeImages($component);

        $component->call('generate');

        $this->assertSame($definition, $component->get('currentDefinition'));
        $this->assertNotEmpty($component->get('instructionLog'));
    }

    /**
     * Pedido del usuario: poder deshacer el último refinamiento sin gastar
     * otra llamada a la IA. Refina dos veces (A → B) y confirma que
     * "Deshacer" vuelve exactamente a A, no a un estado vacío.
     */
    public function test_undo_last_refinement_restores_the_definition_before_the_last_generate(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $definitionA = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]];
        $definitionB = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 2, 'h' => 2, 'd' => 2, 'texture' => 'iron', 'rotationY' => 0, 'collidable' => false],
        ]];

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template]);
        $this->attachFakeImages($component);

        $this->fakeGenerator($definitionA);
        $component->call('generate')->assertSet('currentDefinition', $definitionA);

        $this->fakeGenerator($definitionB);
        $component->call('generate')
            ->assertSet('currentDefinition', $definitionB)
            ->assertSet('canUndoLastRefinement', true);

        $component->call('undoLastRefinement')
            ->assertSet('currentDefinition', $definitionA)
            ->assertSet('canUndoLastRefinement', false);

        $this->assertSame($definitionA, $template->fresh()->ai_draft_definition);
    }

    /**
     * Deshacer solo tiene un nivel de historial (el pedido es "el último
     * cambio") — volver a llamarlo sin haber refinado de nuevo no debe
     * hacer nada.
     */
    public function test_undo_last_refinement_is_a_no_op_when_there_is_nothing_to_undo(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->assertSet('canUndoLastRefinement', false)
            ->call('undoLastRefinement')
            ->assertSet('currentDefinition', null);
    }

    /**
     * Si la generación falla, no hay nada nuevo que deshacer — el botón no
     * debe aparecer.
     */
    public function test_a_failed_generation_does_not_enable_undo(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $this->fakeFailingGenerator();

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template]);
        $this->attachFakeImages($component);

        $component->call('generate')
            ->assertSet('canUndoLastRefinement', false);
    }

    /**
     * Pedido del usuario: al terminar de generar/refinar con éxito, el
     * campo de instrucciones debe quedar en blanco (ya se aplicó, no tiene
     * sentido dejarlo ahí para la siguiente vuelta).
     */
    public function test_a_successful_generation_clears_the_instructions_field(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $this->fakeGenerator(['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]]);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'una mesa pequeña');

        $this->attachFakeImages($component);

        $component->call('generate')
            ->assertSet('instructions', '');
    }

    /**
     * Contraparte del test anterior: si la generación falla, el texto debe
     * quedarse tal cual para que el admin pueda corregirlo y reintentar sin
     * reescribirlo desde cero.
     */
    public function test_a_failed_generation_keeps_the_instructions_field(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $this->fakeFailingGenerator();

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'hazlo más alto');

        $this->attachFakeImages($component);

        $component->call('generate')
            ->assertSet('instructions', 'hazlo más alto');
    }

    public function test_a_failed_generation_keeps_the_previous_definition(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $previousDefinition = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'iron', 'rotationY' => 0, 'collidable' => false],
        ]];
        $template = $this->makeTemplate([
            'asset_input_mode' => 'ia_voxel',
            'model_definition' => $previousDefinition,
        ]);

        $this->fakeFailingGenerator();

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'hazlo más alto');

        $this->attachFakeImages($component);

        $component->call('generate');

        $this->assertSame($previousDefinition, $component->get('currentDefinition'));
    }

    public function test_save_rejects_an_invalid_definition_without_persisting_it(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template]);
        $component->set('currentDefinition', ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'no-existe', 'rotationY' => 0, 'collidable' => false],
        ]]);

        $component->call('save');

        $this->assertNull($template->fresh()->model_definition);
    }

    public function test_save_persists_a_valid_definition_with_its_bounding_box(): void
    {
        $this->makeAdmin();
        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template]);
        $component->set('currentDefinition', ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 2, 'h' => 1, 'd' => 2, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]]);

        $component->call('save');

        $fresh = $template->fresh();
        $this->assertNotNull($fresh->model_definition);
        $this->assertSame(2.0, $fresh->max_width);
        $this->assertSame(2.0, $fresh->max_depth);
        $this->assertSame(1.0, $fresh->max_height);
    }

    /**
     * Pedido del usuario: la miniatura del catálogo (si existe) es una
     * referencia visual adicional del objeto completo — debe enviarse junto
     * a las 3 fotos subidas, pero como es un asset persistente del template
     * (no un archivo temporal de esta generación), nunca debe borrarse.
     */
    public function test_generate_includes_the_thumbnail_as_an_extra_reference_image_without_deleting_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('immersive-object-templates/thumb.png', 'fake-thumb-bytes');

        $this->makeAdmin();
        $template = $this->makeTemplate([
            'asset_input_mode' => 'ia_voxel',
            'thumbnail_path' => 'immersive-object-templates/thumb.png',
        ]);

        $captured = null;
        $this->fakeRecordingGenerator(['version' => 1, 'boxes' => []], $captured);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'instrucciones');

        $this->attachFakeImages($component);
        $component->call('generate');

        $this->assertCount(4, $captured['imagePaths']);
        $this->assertContains('immersive-object-templates/thumb.png', $captured['imagePaths']);
        $this->assertTrue(Storage::disk('public')->exists('immersive-object-templates/thumb.png'));
    }

    public function test_generate_passes_the_template_max_boxes_in_context(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate([
            'asset_input_mode' => 'ia_voxel',
            'max_boxes' => 150,
        ]);

        $captured = null;
        $this->fakeRecordingGenerator(['version' => 1, 'boxes' => []], $captured);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'instrucciones');

        $this->attachFakeImages($component);
        $component->call('generate');

        $this->assertSame(150, $captured['context']['max_boxes']);
    }

    /**
     * Bug real reportado: "Colores permitidos" no tenía ningún efecto en la
     * generación. Confirma que el componente resuelve los hex guardados a
     * nombres de textura (VoxelPaletteMatcher) y los manda en el contexto
     * que llega al generador.
     */
    public function test_generate_translates_allowed_colors_to_allowed_textures_in_context(): void
    {
        Storage::fake('public');
        $this->makeAdmin();
        $template = $this->makeTemplate([
            'asset_input_mode' => 'ia_voxel',
            'allowed_colors' => ['#6d4b30', '#f4ebe2'],
        ]);

        $captured = null;
        $this->fakeRecordingGenerator(['version' => 1, 'boxes' => []], $captured);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'instrucciones');

        $this->attachFakeImages($component);
        $component->call('generate');

        $this->assertSame(['#6d4b30', '#f4ebe2'], $captured['context']['allowed_colors']);
        $this->assertSame(['wood', 'white'], $captured['context']['allowed_textures']);
    }

    public function test_save_persists_the_last_reference_images_for_future_modal_opens(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template]);
        $this->attachFakeImages($component);
        $component->call('save');

        $template->refresh();

        $this->assertNotNull($template->ai_reference_images);
        $this->assertTrue(Storage::disk('public')->exists($template->referenceImagePath('front')));
        $this->assertTrue(Storage::disk('public')->exists($template->referenceImagePath('side')));
        $this->assertTrue(Storage::disk('public')->exists($template->referenceImagePath('top')));

        Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->assertSet('instructionLog', $template->ai_instruction_log ?? []);
    }

    public function test_generate_persists_the_log_and_the_ai_draft_definition(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $template = $this->makeTemplate(['asset_input_mode' => 'ia_voxel']);
        $definition = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]];

        $this->fakeGenerator($definition);

        $component = Livewire::test(ImmersiveObjectTemplateAiGenerator::class, ['template' => $template])
            ->set('instructions', 'hazlo más alto');

        $this->attachFakeImages($component);
        $component->call('generate');

        $fresh = $template->fresh();

        $this->assertSame($definition, $fresh->ai_draft_definition);
        $this->assertNotEmpty($fresh->ai_instruction_log);
        $this->assertSame('hazlo más alto', $fresh->ai_instruction_log[0]['text']);
    }
}
