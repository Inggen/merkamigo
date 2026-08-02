<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\UserDevice;

/**
 * Revoca (elimina) un dispositivo registrado (5.2 del TODO) — deja de
 * recibir push de inmediato.
 */
class RevokeDevice
{
    public function handle(UserDevice $device): void
    {
        $device->delete();
    }
}
