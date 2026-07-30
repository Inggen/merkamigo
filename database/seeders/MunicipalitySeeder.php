<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Municipios activos para la experiencia pública inicial.
 */
class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Bogotá', 'department' => 'Bogotá, D.C.', 'latitude' => 4.7110000, 'longitude' => -74.0721000],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'latitude' => 4.9185700, 'longitude' => -74.0279900],
            ['name' => 'Chía', 'department' => 'Cundinamarca', 'latitude' => 4.8623200, 'longitude' => -74.0327900],
            ['name' => 'Cogua', 'department' => 'Cundinamarca', 'latitude' => 5.0618900, 'longitude' => -73.9792500],
            ['name' => 'Cota', 'department' => 'Cundinamarca', 'latitude' => 4.8093800, 'longitude' => -74.1015400],
            ['name' => 'Gachancipá', 'department' => 'Cundinamarca', 'latitude' => 4.9911100, 'longitude' => -73.8715400],
            ['name' => 'Nemocón', 'department' => 'Cundinamarca', 'latitude' => 5.0676700, 'longitude' => -73.8776900],
            ['name' => 'Sopó', 'department' => 'Cundinamarca', 'latitude' => 4.9075000, 'longitude' => -73.9384000],
            ['name' => 'Tabio', 'department' => 'Cundinamarca', 'latitude' => 4.9166700, 'longitude' => -74.1000000],
            ['name' => 'Tenjo', 'department' => 'Cundinamarca', 'latitude' => 4.8727000, 'longitude' => -74.1443500],
            ['name' => 'Tocancipá', 'department' => 'Cundinamarca', 'latitude' => 4.9653100, 'longitude' => -73.9130100],
            ['name' => 'Zipaquirá', 'department' => 'Cundinamarca', 'latitude' => 5.0220800, 'longitude' => -74.0048100],
        ] as $municipality) {
            Municipality::query()->updateOrCreate(
                ['slug' => Str::slug($municipality['name'])],
                [
                    'name' => $municipality['name'],
                    'department' => $municipality['department'],
                    'is_active' => true,
                    'latitude' => $municipality['latitude'],
                    'longitude' => $municipality['longitude'],
                ],
            );
        }
    }
}
