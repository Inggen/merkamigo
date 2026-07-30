<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Municipios piloto definidos en 0.1 del TODO: Cajicá y Zipaquirá.
 */
class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Cajicá' => ['latitude' => 4.9185700, 'longitude' => -74.0279900],
            'Zipaquirá' => ['latitude' => 5.0220800, 'longitude' => -74.0048100],
        ] as $name => $coordinates) {
            Municipality::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'department' => 'Cundinamarca',
                    'is_active' => true,
                    ...$coordinates,
                ],
            );

            Municipality::query()
                ->where('slug', Str::slug($name))
                ->update($coordinates);
        }
    }
}
