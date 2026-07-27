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
        foreach (['Cajicá', 'Zipaquirá'] as $name) {
            Municipality::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'department' => 'Cundinamarca', 'is_active' => true],
            );
        }
    }
}
