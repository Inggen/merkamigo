<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Report;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra un reporte de contenido (1.3/1.4/1.9 del TODO: "reportar
 * contenido"). Disponible tanto para visitantes como para clientes
 * registrados — no exige cuenta.
 */
class SubmitReport
{
    public function handle(Model $reportable, string $reason, ?string $details, ?string $reporterEmail): Report
    {
        return Report::create([
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
            'details' => $details,
            'reporter_email' => $reporterEmail,
            'status' => Report::PENDIENTE,
        ]);
    }
}
