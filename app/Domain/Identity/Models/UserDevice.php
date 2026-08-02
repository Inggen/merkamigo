<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dispositivo registrado para notificaciones push (5.2 del TODO). Un
 * `push_token` identifica un dispositivo de forma única — reinstalar la
 * app o volver a registrar el mismo token actualiza la misma fila en vez
 * de crear un duplicado (mismo criterio "una fila por entidad" ya usado
 * en el proyecto para ofertas y verificaciones).
 *
 * @property Carbon|null $last_seen_at
 */
class UserDevice extends Model
{
    public const FCM = 'fcm';

    public const APNS = 'apns';

    public const WEB = 'web';

    protected $fillable = ['user_id', 'platform', 'push_token', 'app_version', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
