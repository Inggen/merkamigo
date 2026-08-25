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
        $this->seedCharacters();

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

    /**
     * Personajes voxel editables desde Objetos 3D. `firstOrCreate` es
     * intencional: volver a ejecutar el seeder nunca pisa una anatomía que
     * el administrador ya haya personalizado en el editor espacial.
     */
    private function seedCharacters(): void
    {
        foreach ([
            'personaje-voxel-hombre' => ['name' => 'Personaje voxel — Hombre', 'female' => false],
            'personaje-voxel-mujer' => ['name' => 'Personaje voxel — Mujer', 'female' => true],
        ] as $slug => $character) {
            ImmersiveObjectTemplate::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $character['name'],
                    'category' => 'personaje',
                    'asset_input_mode' => 'ia_voxel',
                    'max_width' => 2.5,
                    'max_depth' => 1.5,
                    'max_height' => 4.1,
                    'max_boxes' => 30,
                    'front_axis_rotation' => 0,
                    'status' => 'publicada',
                    'model_definition' => $this->characterDefinition($character['female']),
                ],
            );
        }
    }

    /** @return array<string, mixed> */
    private function characterDefinition(bool $female): array
    {
        $groups = [
            ['id' => 'head', 'name' => 'Cabeza y cabello'],
            ['id' => 'torso', 'name' => 'Torso'],
            ['id' => 'left-arm', 'name' => 'Brazo izquierdo'],
            ['id' => 'right-arm', 'name' => 'Brazo derecho'],
            ['id' => 'left-leg', 'name' => 'Pierna izquierda'],
            ['id' => 'right-leg', 'name' => 'Pierna derecha'],
        ];

        $boxes = [
            $this->characterBox(0, 3.28, 0, 1.28, 1.28, 1.16, 'skin', 'head'),
            $this->characterBox(0, 3.79, -0.06, 1.34, 0.34, 1.22, 'woodDark', 'head'),
            $this->characterBox(0, $female ? 3.06 : 3.4, -0.55, 1.3, $female ? 1.5 : 0.72, 0.22, 'woodDark', 'head'),
            $this->characterBox(0.55, 3.48, -0.08, 0.22, 0.72, 1.08, 'woodDark', 'head'),
            $this->characterBox(0.27, 3.66, 0.55, 0.72, 0.28, 0.12, 'woodDark', 'head'),
            $this->characterBox(-0.28, 3.35, 0.595, 0.14, 0.18, 0.06, 'woodDark', 'head'),
            $this->characterBox(0.28, 3.35, 0.595, 0.14, 0.18, 0.06, 'woodDark', 'head'),
            $this->characterBox(0, 3, 0.595, 0.28, 0.07, 0.06, 'woodDark', 'head'),
            $this->characterBox(0, 2.04, 0, 1.48, 1.62, 0.96, $female ? 'flower' : 'shirt', 'torso'),
            $this->characterBox(-0.98, 1.95, 0, 0.5, 1.62, 0.54, 'skin', 'left-arm'),
            $this->characterBox(0.98, 1.95, 0, 0.5, 1.62, 0.54, 'skin', 'right-arm'),
            $this->characterBox(-0.39, 0.75, 0, 0.58, 1.5, 0.62, 'pants', 'left-leg'),
            $this->characterBox(0.39, 0.75, 0, 0.58, 1.5, 0.62, 'pants', 'right-leg'),
            $this->characterBox(-0.39, 0.2, 0.12, 0.63, 0.34, 0.88, 'woodDark', 'left-leg'),
            $this->characterBox(0.39, 0.2, 0.12, 0.63, 0.34, 0.88, 'woodDark', 'right-leg'),
        ];

        if (! $female) {
            // Dos bandas frontales aportan el patrón azul/rojo de la referencia
            // y quedan como cajas independientes para recolorearlas o borrarlas.
            $boxes[] = $this->characterBox(-0.31, 2.04, 0.495, 0.13, 1.55, 0.05, 'coral', 'torso');
            $boxes[] = $this->characterBox(0.31, 2.04, 0.495, 0.13, 1.55, 0.05, 'coral', 'torso');
            $boxes[] = $this->characterBox(0, 2.3, 0.5, 1.42, 0.12, 0.05, 'coral', 'torso');
            $boxes[] = $this->characterBox(0, 1.79, 0.5, 1.42, 0.12, 0.05, 'coral', 'torso');
        }

        return ['version' => 1, 'groups' => $groups, 'boxes' => $boxes];
    }

    /** @return array<string, float|bool|string> */
    private function characterBox(
        float $x,
        float $y,
        float $z,
        float $width,
        float $height,
        float $depth,
        string $texture,
        string $groupId,
    ): array {
        return [
            'x' => $x, 'y' => $y, 'z' => $z,
            'w' => $width, 'h' => $height, 'd' => $depth,
            'texture' => $texture,
            'rotationX' => 0, 'rotationY' => 0, 'rotationZ' => 0,
            'collidable' => false,
            'groupId' => $groupId,
        ];
    }
}
