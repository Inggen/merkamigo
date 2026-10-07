<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Municipios activos para la experiencia pública inicial. `description`
 * agregado el 2026-10-03 (optimización GEO): hechos reales y verificables
 * sobre cada municipio, no datos inventados sobre Merkamigo — contenido
 * editorial inicial, editable después desde Filament sin desplegar código.
 */
class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Bogotá', 'department' => 'Bogotá, D.C.', 'latitude' => 4.7110000, 'longitude' => -74.0721000, 'description' => 'Bogotá es la capital de Colombia y la ciudad más poblada del país, centro económico, político y cultural. En su Plaza de Merkamigo puedes encontrar negocios locales de barrios y zonas de toda la ciudad.'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'latitude' => 4.9185700, 'longitude' => -74.0279900, 'description' => 'Cajicá es un municipio de la Sabana Norte de Cundinamarca, conocido por su tradición lechera y sus parques a orillas del río Bogotá. En su Plaza de Merkamigo encuentras negocios locales de alimentos, servicios y más.'],
            ['name' => 'Chía', 'department' => 'Cundinamarca', 'latitude' => 4.8623200, 'longitude' => -74.0327900, 'description' => 'Chía, conocida como "la ciudad de la luna" por su nombre de origen muysca, es uno de los municipios con mayor oferta comercial y gastronómica de la Sabana de Bogotá. En su Plaza de Merkamigo puedes descubrir negocios locales.'],
            ['name' => 'Cogua', 'department' => 'Cundinamarca', 'latitude' => 5.0618900, 'longitude' => -73.9792500, 'description' => 'Cogua es un municipio agrícola y ganadero de la Sabana Centro de Cundinamarca, cercano a Zipaquirá. En su Plaza de Merkamigo puedes encontrar los negocios locales del municipio.'],
            ['name' => 'Cota', 'department' => 'Cundinamarca', 'latitude' => 4.8093800, 'longitude' => -74.1015400, 'description' => 'Cota es un municipio de Cundinamarca con un resguardo indígena muysca activo y una concurrida zona de restaurantes y comercio sobre la autopista Medellín, muy cerca de Bogotá. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Gachancipá', 'department' => 'Cundinamarca', 'latitude' => 4.9911100, 'longitude' => -73.8715400, 'description' => 'Gachancipá es un municipio de la Sabana Norte de Cundinamarca, sobre la vía que conecta Bogotá con Tunja y Boyacá. En su Plaza de Merkamigo puedes encontrar los negocios locales del municipio.'],
            ['name' => 'Nemocón', 'department' => 'Cundinamarca', 'latitude' => 5.0676700, 'longitude' => -73.8776900, 'description' => 'Nemocón es un municipio de Cundinamarca reconocido por su histórica mina de sal, uno de los atractivos turísticos de la región. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Sopó', 'department' => 'Cundinamarca', 'latitude' => 4.9075000, 'longitude' => -73.9384000, 'description' => 'Sopó es un municipio de Cundinamarca con fuerte tradición en la industria láctea y zona rural cercana al embalse de Tominé. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Tabio', 'department' => 'Cundinamarca', 'latitude' => 4.9166700, 'longitude' => -74.1000000, 'description' => 'Tabio es un municipio de Cundinamarca conocido por sus aguas termales y su ambiente rural tranquilo, cerca de Tenjo y Cota. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Tenjo', 'department' => 'Cundinamarca', 'latitude' => 4.8727000, 'longitude' => -74.1443500, 'description' => 'Tenjo es un municipio rural de Cundinamarca con fincas y clubes campestres, ubicado sobre la autopista Medellín al occidente de Bogotá. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Tocancipá', 'department' => 'Cundinamarca', 'latitude' => 4.9653100, 'longitude' => -73.9130100, 'description' => 'Tocancipá es un municipio de Cundinamarca conocido por su autódromo y su cercanía al Parque Jaime Duque, además de contar con varias zonas industriales. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
            ['name' => 'Zipaquirá', 'department' => 'Cundinamarca', 'latitude' => 5.0220800, 'longitude' => -74.0048100, 'description' => 'Zipaquirá es capital de la provincia de Sabana Centro de Cundinamarca, mundialmente conocida por su Catedral de Sal construida dentro de una mina subterránea. En su Plaza de Merkamigo puedes encontrar sus negocios locales.'],
        ] as $municipality) {
            Municipality::query()->updateOrCreate(
                ['slug' => Str::slug($municipality['name'])],
                [
                    'name' => $municipality['name'],
                    'department' => $municipality['department'],
                    'description' => $municipality['description'],
                    'is_active' => true,
                    'latitude' => $municipality['latitude'],
                    'longitude' => $municipality['longitude'],
                ],
            );
        }
    }
}
