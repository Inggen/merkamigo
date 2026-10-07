<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBlockedDate;
use Illuminate\Database\QueryException;

/**
 * Fechas en las que un negocio no recibe eventos (Fase 2 · pestaña
 * Agenda). `business_id`+`date` es único en BD — aquí se traduce ese
 * choque en un mensaje legible en vez de dejar pasar la excepción SQL.
 */
class ManageEventBlockedDate
{
    public function add(Business $business, string $date, ?string $reason = null): EventBlockedDate
    {
        try {
            return $business->eventBlockedDates()->create(['date' => $date, 'reason' => $reason]);
        } catch (QueryException $e) {
            throw new EventActionException('Esa fecha ya está bloqueada.', previous: $e);
        }
    }

    public function remove(EventBlockedDate $blockedDate): void
    {
        $blockedDate->delete();
    }
}
