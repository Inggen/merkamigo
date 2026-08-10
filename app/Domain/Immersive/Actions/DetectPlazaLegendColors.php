<?php

namespace App\Domain\Immersive\Actions;

use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Support\ColorBlobDetector;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * IMM-013 del TODO inmersivo (redefinido): lee la imagen de leyenda de una
 * plaza y crea/actualiza una `PlazaLegendEntry` por cada color distinto
 * encontrado, sin tocar las que el administrador ya confirmó.
 */
class DetectPlazaLegendColors
{
    public function __construct(private readonly ColorBlobDetector $detector = new ColorBlobDetector) {}

    /**
     * @return array{detected: int, created: int, updated: int}
     */
    public function execute(ImmersivePlaza $plaza): array
    {
        if (blank($plaza->legend_image_path)) {
            throw new RuntimeException('La plaza no tiene una imagen de leyenda cargada.');
        }

        $absolutePath = Storage::disk('public')->path($plaza->legend_image_path);
        $colors = $this->detector->detectDistinctColors($absolutePath);

        $created = 0;
        $updated = 0;

        foreach ($colors as $color) {
            $entry = $plaza->legendEntries()->where('color_hex', $color['color_hex'])->first();

            if ($entry) {
                $entry->update(['detected_pixel_count' => $color['pixel_count']]);
                $updated++;

                continue;
            }

            $plaza->legendEntries()->create([
                'color_hex' => $color['color_hex'],
                'detected_pixel_count' => $color['pixel_count'],
                'status' => 'pendiente',
            ]);
            $created++;
        }

        return ['detected' => count($colors), 'created' => $created, 'updated' => $updated];
    }
}
