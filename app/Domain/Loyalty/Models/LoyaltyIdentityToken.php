<?php

namespace App\Domain\Loyalty\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Token de identificación del cliente (TODO_Merkapuntos.md, F1.8): el QR
 * que el negocio escanea para SABER QUIÉN ES el cliente, nunca para
 * autorizar un retiro de puntos. Solo se guarda el hash; el token crudo se
 * entrega una vez al cliente (en la sección Merkapuntos) y se puede rotar
 * si se sospecha que se expuso.
 */
class LoyaltyIdentityToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'token_encrypted',
        'rotated_at',
    ];

    protected function casts(): array
    {
        return [
            // Cast nativo de Eloquent: cifra/descifra con APP_KEY de forma
            // transparente. Nunca se guarda el token crudo en texto plano.
            'token_encrypted' => 'encrypted',
            'rotated_at' => 'datetime',
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
