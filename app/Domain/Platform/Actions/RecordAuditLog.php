<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Registra una acción sensible (ingreso, cambio de permisos, creación de
 * negocio, moderación...) para trazabilidad (0.5 y 0.6 del TODO exigen
 * auditoría de estas acciones).
 */
class RecordAuditLog
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(?Authenticatable $user, string $action, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }
}
