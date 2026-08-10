<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use Illuminate\Database\Seeder;

/**
 * IMM-011 del TODO inmersivo: publica la experiencia de los dos labs que ya
 * existían como código antes de que `immersive_experiences` existiera,
 * para que `Municipality::immersiveLabUrl()` (ahora data-driven en vez de
 * un `match($slug)` hardcodeado) siga resolviendo a las mismas rutas.
 *
 * Desde IMM-010 una experiencia no se puede publicar sin al menos una
 * plaza con punto de aparición y límites navegables (`assertReadyToPublish`
 * en el modelo), así que cada una recibe una "Plaza 1" mínima antes de
 * marcarse como publicada.
 */
class ImmersiveExperienceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['municipality_slug' => 'zipaquira', 'name' => 'Plaza voxel de Zipaquirá', 'slug' => 'zipaquira', 'route_name' => 'labs.zipa-inmersiva'],
            ['municipality_slug' => 'cajica', 'name' => 'Parque voxel de Cajicá', 'slug' => 'cajica', 'route_name' => 'labs.cajica-inmersiva'],
        ] as $experience) {
            $municipality = Municipality::query()->where('slug', $experience['municipality_slug'])->first();

            if (! $municipality) {
                continue;
            }

            $record = ImmersiveExperience::query()->updateOrCreate(
                ['slug' => $experience['slug']],
                [
                    'municipality_id' => $municipality->id,
                    'name' => $experience['name'],
                    'route_name' => $experience['route_name'],
                ],
            );

            $record->plazas()->updateOrCreate(
                ['order' => 1],
                [
                    'name' => 'Plaza 1',
                    'capacity' => 20,
                    'status' => 'activa',
                    'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
                    'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
                ],
            );

            $record->update(['status' => 'publicada']);
        }
    }
}
