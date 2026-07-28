<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Report;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use InvalidArgumentException;

/**
 * Resuelve o descarta un reporte (1.9 del TODO: "motivos estandarizados y
 * notificación al afectado" — aquí el motivo queda en `resolution_note` y
 * auditado; no hay canal de notificación real todavía).
 */
class ResolveReport
{
    public function handle(Report $report, User $moderator, string $status, ?string $note): void
    {
        if (! in_array($status, [Report::RESUELTO, Report::DESCARTADO], true)) {
            throw new InvalidArgumentException("Estado de resolución inválido: {$status}");
        }

        $report->update([
            'status' => $status,
            'resolution_note' => $note,
            'resolved_by' => $moderator->id,
            'resolved_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'report.resolved', $report, [
            'status' => $status,
        ]);
    }
}
