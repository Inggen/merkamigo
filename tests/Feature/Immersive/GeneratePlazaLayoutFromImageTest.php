<?php

namespace Tests\Feature\Immersive;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Actions\DetectPlazaLegendColors;
use App\Domain\Immersive\Actions\GeneratePlazaLayoutFromImage;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandZone;
use GdImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * IMM-013 del TODO inmersivo (redefinido): flujo completo admin sube
 * plano + leyenda → el sistema detecta colores → el admin confirma cada
 * uno contra el catálogo → "Generar ubicaciones" crea los stand_slots y
 * props reales. Usa imágenes sintéticas (GD) en vez de un archivo de
 * ejemplo, para que el test sea determinista.
 */
class GeneratePlazaLayoutFromImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_stand_slot_and_a_prop_from_synthetic_plan_and_legend_images(): void
    {
        Storage::fake('public');

        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de Zipaquirá',
            'slug' => 'zipaquira',
        ]);

        $plaza = ImmersivePlaza::create([
            'immersive_experience_id' => $experience->id,
            'name' => 'Plaza 1',
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
            'reference_image_width' => 200,
            'reference_image_height' => 200,
        ]);

        $standTemplate = ImmersiveObjectTemplate::create([
            'name' => 'Stand estándar', 'slug' => 'stand-estandar', 'category' => 'stand',
            'max_width' => 4, 'max_depth' => 4, 'max_height' => 3,
        ]);
        $treeTemplate = ImmersiveObjectTemplate::create([
            'name' => 'Árbol grande', 'slug' => 'arbol-grande', 'category' => 'arbol',
            'max_width' => 3, 'max_depth' => 3, 'max_height' => 8,
        ]);

        // Zona que cubre el cuadrante negativo, donde caerá el stand
        // detectado (ver cálculo de world position abajo).
        $zone = StandZone::create([
            'immersive_plaza_id' => $plaza->id,
            'name' => 'Zona sur',
            'polygon' => ['points' => [
                ['x' => -50, 'z' => -50], ['x' => 0, 'z' => -50], ['x' => 0, 'z' => 0], ['x' => -50, 'z' => 0],
            ]],
        ]);

        // Cian en (20,20)-(50,50), centro (35,35) → world (-32.5,-32.5): cae en la zona sur.
        // Verde en (150,150)-(180,180), centro (165,165) → world (32.5,32.5): fuera de la zona (prop, no necesita zona).
        $planPath = $this->syntheticPlanImage();
        $legendPath = $this->syntheticLegendImage();

        Storage::disk('public')->put('immersive-plazas/plan.png', file_get_contents($planPath));
        Storage::disk('public')->put('immersive-plazas/legend.png', file_get_contents($legendPath));

        $plaza->update([
            'reference_image_path' => 'immersive-plazas/plan.png',
            'legend_image_path' => 'immersive-plazas/legend.png',
        ]);

        $detectSummary = (new DetectPlazaLegendColors)->execute($plaza->fresh());

        $this->assertSame(2, $detectSummary['detected']);
        $this->assertDatabaseCount('plaza_legend_entries', 2);

        $plaza->legendEntries()->where('color_hex', '#1ec8c8')->first()
            ->update(['object_template_id' => $standTemplate->id, 'status' => 'confirmado']);
        $plaza->legendEntries()->where('color_hex', '#288c28')->first()
            ->update(['object_template_id' => $treeTemplate->id, 'status' => 'confirmado']);

        $this->assertTrue($plaza->fresh()->legendIsFullyConfirmed());

        $generateSummary = (new GeneratePlazaLayoutFromImage)->execute($plaza->fresh());

        $this->assertSame(1, $generateSummary['stands_created']);
        $this->assertSame(1, $generateSummary['props_created']);
        $this->assertSame([], $generateSummary['skipped']);

        $this->assertDatabaseCount('stand_slots', 1);
        $this->assertDatabaseCount('immersive_plaza_props', 1);

        $slot = $zone->slots()->first();
        $this->assertNotNull($slot);
        $this->assertSame('auto_detected', $slot->source);
        $this->assertSame($standTemplate->id, $slot->stand_template_id);
        $this->assertEqualsWithDelta(-32.5, $slot->world_position['x'], 2.0);
        $this->assertEqualsWithDelta(-32.5, $slot->world_position['z'], 2.0);

        $prop = $plaza->props()->first();
        $this->assertNotNull($prop);
        $this->assertSame('auto_detected', $prop->source);
        $this->assertSame($treeTemplate->id, $prop->object_template_id);
        $this->assertEqualsWithDelta(32.5, $prop->world_position['x'], 2.0);
        $this->assertEqualsWithDelta(32.5, $prop->world_position['z'], 2.0);
    }

    private function syntheticPlanImage(): string
    {
        $image = $this->blankCanvas(200, 200);

        $cyan = imagecolorallocate($image, 30, 200, 200);
        imagefilledrectangle($image, 20, 20, 50, 50, $cyan);

        $green = imagecolorallocate($image, 40, 140, 40);
        imagefilledrectangle($image, 150, 150, 180, 180, $green);

        return $this->savePng($image);
    }

    private function syntheticLegendImage(): string
    {
        $image = $this->blankCanvas(200, 100);

        $cyan = imagecolorallocate($image, 30, 200, 200);
        imagefilledrectangle($image, 10, 10, 30, 30, $cyan);

        $green = imagecolorallocate($image, 40, 140, 40);
        imagefilledrectangle($image, 10, 50, 30, 70, $green);

        $black = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 3, 40, 15, 'Stand', $black);
        imagestring($image, 3, 40, 55, 'Arbol', $black);

        return $this->savePng($image);
    }

    private function blankCanvas(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        return $image;
    }

    private function savePng(GdImage $image): string
    {
        $path = tempnam(sys_get_temp_dir(), 'immersive-e2e-').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
