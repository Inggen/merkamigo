<?php

namespace Database\Seeders;

use App\Domain\Events\Models\EventEquipmentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catálogo GLOBAL de tipos de equipo para eventos
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 1): "sonido,
 * micrófono y televisor como opciones iniciales". `updateOrCreate` por
 * slug — seguro de re-ejecutar, no borra los tipos que un negocio ya
 * tenga seleccionados en `event_business_equipment`.
 */
class EventEquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Sonido' => 'speaker-wave',
            'Micrófono' => 'microphone',
            'Televisor' => 'tv',
        ];

        foreach (array_keys($types) as $position => $name) {
            EventEquipmentType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $types[$name], 'position' => $position, 'is_active' => true],
            );
        }
    }
}
