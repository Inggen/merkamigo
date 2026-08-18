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
        $this->seedCollisionBarrier();

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

    /**
     * Pedido del usuario: un "tipo de objeto especial" estrictamente
     * colisionante — semitransparente azul claro con bordes en azul más
     * fuerte (`VoxelDefinitionValidator::COLLISION_BARRIER_TEXTURE`,
     * `createVoxelTextures()` en voxel-plaza-engine.js). Caja única de
     * 2×2×2 como punto de partida razonable; el admin la redimensiona con
     * el gizmo de Escalar según la barrera que necesite. `collidable` en
     * verdadero porque el servidor lo exige para esta textura.
     */
    private function seedCollisionBarrier(): void
    {
        ImmersiveObjectTemplate::query()->updateOrCreate(
            ['slug' => 'barrera-de-colision'],
            [
                'name' => 'Barrera de colisión',
                'category' => 'barrera',
                'asset_input_mode' => 'ia_voxel',
                'max_width' => 20,
                'max_depth' => 20,
                'max_height' => 10,
                'max_boxes' => 10,
                'front_axis_rotation' => 0,
                'status' => 'publicada',
                'model_definition' => [
                    'version' => 1,
                    'boxes' => [[
                        'x' => 0, 'y' => 1, 'z' => 0,
                        'w' => 2, 'h' => 2, 'd' => 2,
                        'texture' => 'collisionBarrier',
                        'rotationX' => 0, 'rotationY' => 0, 'rotationZ' => 0,
                        'collidable' => true,
                    ]],
                ],
            ],
        );
    }
}
