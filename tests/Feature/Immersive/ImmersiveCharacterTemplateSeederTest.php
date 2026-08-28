<?php

namespace Tests\Feature\Immersive;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use Database\Seeders\ImmersiveObjectTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImmersiveCharacterTemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_editable_and_valid_character_templates(): void
    {
        $this->seed(ImmersiveObjectTemplateSeeder::class);

        $templates = ImmersiveObjectTemplate::query()
            ->whereIn('slug', ['personaje-voxel-hombre', 'personaje-voxel-mujer'])
            ->get();

        $this->assertCount(2, $templates);

        foreach ($templates as $template) {
            $this->assertSame('personaje', $template->category);
            $this->assertSame('ia_voxel', $template->asset_input_mode);
            $this->assertSame('publicada', $template->status);
            $this->assertNotEmpty($template->model_definition['groups']);

            app(VoxelDefinitionValidator::class)->validate($template->model_definition, $template);
        }
    }

    public function test_reseeding_does_not_overwrite_a_customized_character(): void
    {
        $this->seed(ImmersiveObjectTemplateSeeder::class);

        $template = ImmersiveObjectTemplate::query()
            ->where('slug', 'personaje-voxel-hombre')
            ->firstOrFail();
        $definition = $template->model_definition;
        $definition['boxes'][0]['texture'] = 'coral';
        $template->update(['model_definition' => $definition]);

        $this->seed(ImmersiveObjectTemplateSeeder::class);

        $this->assertSame('coral', $template->fresh()->model_definition['boxes'][0]['texture']);
    }

    public function test_it_seeds_the_market_stall_as_a_complete_editable_voxel_object(): void
    {
        $this->seed(ImmersiveObjectTemplateSeeder::class);

        $template = ImmersiveObjectTemplate::query()
            ->where('slug', 'stand-toldo-mercado')
            ->firstOrFail();

        $this->assertSame('stand', $template->builder_key);
        $this->assertSame('ia_voxel', $template->asset_input_mode);
        $this->assertCount(12, $template->model_definition['boxes']);
        $this->assertContains('color', collect($template->model_definition['boxes'])->pluck('texture'));

        app(VoxelDefinitionValidator::class)->validate($template->model_definition, $template);
    }
}
