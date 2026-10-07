<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo GLOBAL de tipos de equipo (administrado por la plataforma,
 * ej. Sonido/Micrófono/Televisor). Un negocio elige de aquí en
 * `EventBusinessEquipment`; esta tabla nunca se edita desde el panel de
 * un negocio.
 */
class EventEquipmentType extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'is_active', 'position'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }
}
