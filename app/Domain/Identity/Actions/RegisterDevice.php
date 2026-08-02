<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\UserDevice;
use App\Models\User;

/**
 * Registra (o actualiza) un dispositivo para notificaciones push (5.2 del
 * TODO). `push_token` es único: si el mismo dispositivo ya estaba
 * registrado por otro usuario (reinstaló la app con otra cuenta), el
 * registro pasa a pertenecer al usuario actual en vez de fallar.
 */
class RegisterDevice
{
    public function handle(User $user, string $platform, string $pushToken, ?string $appVersion = null): UserDevice
    {
        return UserDevice::updateOrCreate(
            ['push_token' => $pushToken],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'app_version' => $appVersion,
                'last_seen_at' => now(),
            ],
        );
    }
}
