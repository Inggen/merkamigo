<?php

namespace App\Domain\Discovery\Actions;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Support\Facades\Cookie;

/**
 * Guarda el municipio preferido del visitante (1.1.1/1.5 del TODO) para que
 * el Inicio de Clientes y la Plaza lo recuerden entre visitas, sin exigir
 * geolocalización. Se guarda en cookie tanto para invitados como para
 * usuarios autenticados: es una preferencia de navegación, no un dato de
 * cuenta que deba sobrevivir a un cambio de dispositivo.
 */
class SetPreferredMunicipality
{
    public function handle(Municipality $municipality): void
    {
        Cookie::queue('municipio', $municipality->slug, 60 * 24 * 365);
    }
}
