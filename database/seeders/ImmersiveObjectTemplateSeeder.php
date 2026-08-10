<?php

namespace Database\Seeders;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use Illuminate\Database\Seeder;

/**
 * IMM-020 del TODO inmersivo: las tres plantillas de stand iniciales.
 * Son voxel procedurales (`builder_key`, ver `standardBuilders` en
 * `public/js/lib/voxel-plaza-engine.js`), no modelos GLB — huella
 * normalizada para que las tres quepan en un slot compatible sin cambiar
 * el área ocupada.
 */
class ImmersiveObjectTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Caseta de madera',
                'slug' => 'stand-caseta-madera',
                'builder_key' => 'standBooth',
                'max_width' => 4.2,
                'max_depth' => 3.8,
                'max_height' => 2.9,
            ],
            [
                'name' => 'Mesa exhibidora',
                'slug' => 'stand-mesa-exhibidora',
                'builder_key' => 'standTable',
                'max_width' => 3.2,
                'max_depth' => 2.4,
                'max_height' => 2.9,
            ],
            [
                'name' => 'Toldo de mercado',
                'slug' => 'stand-toldo-mercado',
                'builder_key' => 'marketStall',
                'max_width' => 3.0,
                'max_depth' => 3.0,
                'max_height' => 5.2,
            ],
        ] as $template) {
            ImmersiveObjectTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'name' => $template['name'],
                    'builder_key' => $template['builder_key'],
                    'category' => 'stand',
                    'max_width' => $template['max_width'],
                    'max_depth' => $template['max_depth'],
                    'max_height' => $template['max_height'],
                    'front_axis_rotation' => 0,
                    'status' => 'publicada',
                ],
            );
        }
    }
}
