<?php

namespace App\Domain\Immersive\Actions;

use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandZone;
use App\Domain\Immersive\Support\ColorBlobDetector;
use App\Domain\Immersive\Support\SpatialGeometry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * IMM-013 del TODO inmersivo (redefinido): lee el plano de una plaza,
 * detecta las manchas de cada color ya confirmado en su leyenda, y crea un
 * `StandSlot` (si el objeto es categoría "stand") o un
 * `ImmersivePlazaProp` (para el resto) por cada una, con
 * `source = 'auto_detected'` para que el administrador las revise.
 *
 * No inventa orientación desde la flecha dibujada en el plano — usa la
 * regla de orientación de la zona que contiene cada punto. Un stand cuyo
 * punto detectado no cae dentro de ninguna zona, o cuya huella no pasa la
 * validación de `StandSlot` (colisión, fuera de zona, zona excluida), se
 * omite y se reporta — no rompe el resto del lote.
 */
class GeneratePlazaLayoutFromImage
{
    public function __construct(private readonly ColorBlobDetector $detector = new ColorBlobDetector) {}

    /**
     * @return array{stands_created: int, props_created: int, skipped: array<int, string>}
     */
    public function execute(ImmersivePlaza $plaza): array
    {
        if (blank($plaza->reference_image_path)) {
            throw new RuntimeException('La plaza no tiene imagen de plano (reference_image_path).');
        }

        if (blank($plaza->navigable_bounds) || blank($plaza->reference_image_width) || blank($plaza->reference_image_height)) {
            throw new RuntimeException('Faltan límites navegables o dimensiones de la imagen: no se puede calibrar imagen→mundo.');
        }

        if (! $plaza->legendIsFullyConfirmed()) {
            throw new RuntimeException('Hay colores de la leyenda sin confirmar todavía.');
        }

        $legendEntries = $plaza->legendEntries()->with('template')->get()->keyBy('color_hex');
        $zones = $plaza->zones()->get();

        $absolutePath = Storage::disk('public')->path($plaza->reference_image_path);
        $blobs = $this->detector->detectBlobsForColors($absolutePath, $legendEntries->keys()->all());

        $standsCreated = 0;
        $propsCreated = 0;
        $skipped = [];

        foreach ($blobs as $blob) {
            $entry = $legendEntries->get($blob['color_hex']);
            $template = $entry?->template;

            if (! $template) {
                $skipped[] = "Color {$blob['color_hex']} sin plantilla asociada.";

                continue;
            }

            $world = $plaza->imageToWorld($blob['x'], $blob['y']);

            if (! $world) {
                $skipped[] = "{$template->name}: no se pudo calcular posición de mundo.";

                continue;
            }

            if ($template->isStand()) {
                $zone = $this->findContainingZone($zones, $world);

                if (! $zone) {
                    $skipped[] = "{$template->name} en ({$blob['x']}, {$blob['y']}): no cae dentro de ninguna zona de stands.";

                    continue;
                }

                try {
                    $zone->slots()->create([
                        'code' => 'AUTO-'.($zone->slots()->count() + 1),
                        'stand_template_id' => $template->id,
                        'image_position' => ['x' => $blob['x'], 'y' => $blob['y']],
                        'world_position' => ['x' => $world['x'], 'y' => 0, 'z' => $world['z']],
                        'max_width' => $template->max_width,
                        'max_depth' => $template->max_depth,
                        'source' => 'auto_detected',
                    ]);
                    $standsCreated++;
                } catch (ValidationException $exception) {
                    $skipped[] = "{$template->name} en zona \"{$zone->name}\": ".collect($exception->errors())->flatten()->first();
                }

                continue;
            }

            $plaza->props()->create([
                'object_template_id' => $template->id,
                'image_position' => ['x' => $blob['x'], 'y' => $blob['y']],
                'world_position' => ['x' => $world['x'], 'y' => 0, 'z' => $world['z']],
                'source' => 'auto_detected',
                'status' => 'borrador',
            ]);
            $propsCreated++;
        }

        return ['stands_created' => $standsCreated, 'props_created' => $propsCreated, 'skipped' => $skipped];
    }

    /**
     * @param  Collection<int, StandZone>  $zones
     * @param  array{x: float, z: float}  $world
     */
    private function findContainingZone($zones, array $world): ?StandZone
    {
        foreach ($zones as $zone) {
            if (SpatialGeometry::pointInPolygon($world, $zone->polygon)) {
                return $zone;
            }
        }

        return null;
    }
}
