<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\SupportTicket;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use InvalidArgumentException;

/**
 * Cambia el estado de una solicitud de soporte (1.9 del TODO), igual
 * patrón que `ResolveReport`: el motivo queda en `resolution_note` y
 * auditado; no hay canal de notificación real todavía.
 */
class ResolveSupportTicket
{
    private const STATUSES = [
        SupportTicket::EN_PROGRESO,
        SupportTicket::RESUELTO,
        SupportTicket::CERRADO,
    ];

    public function handle(SupportTicket $ticket, User $moderator, string $status, ?string $note): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Estado de solicitud de soporte inválido: {$status}");
        }

        $ticket->update([
            'status' => $status,
            'resolution_note' => $note,
            'resolved_by' => $moderator->id,
            'resolved_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'support_ticket.resolved', $ticket, [
            'status' => $status,
        ]);
    }
}
