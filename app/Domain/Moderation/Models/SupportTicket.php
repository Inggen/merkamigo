<?php

namespace App\Domain\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de soporte (1.9 del TODO): un visitante o usuario deja
 * constancia escrita de un problema, como alternativa al enlace directo de
 * WhatsApp en `/soporte`. Un moderador la atiende desde Filament.
 */
class SupportTicket extends Model
{
    public const PENDIENTE = 'pendiente';

    public const EN_PROGRESO = 'en_progreso';

    public const RESUELTO = 'resuelto';

    public const CERRADO = 'cerrado';

    protected $fillable = [
        'user_id', 'contact_email', 'subject', 'message',
        'status', 'resolution_note', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function contactLabel(): string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->contact_email ?? __('Anónimo');
    }
}
