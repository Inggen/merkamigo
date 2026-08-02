<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Notifications\WeeklyBusinessReport;
use App\Domain\Businesses\Models\Business;

/**
 * Envía el informe semanal (4.5 del TODO) a los negocios publicados con
 * actividad la última semana — no se molesta a un negocio sin ninguna
 * visita ni contacto con un correo vacío.
 */
class SendWeeklyBusinessReports
{
    public function handle(): int
    {
        $businesses = Business::query()
            ->where('status', 'publicado')
            ->with('members')
            ->get();

        $sent = 0;

        foreach ($businesses as $business) {
            $metrics = app(CalculateReadableMetrics::class)->handle($business);
            $funnel = app(CalculateConversionFunnel::class)->handle($business);

            if ($metrics['total_views'] === 0 && $metrics['total_whatsapp_clicks'] === 0) {
                continue;
            }

            $members = $business->members;

            if ($members->isEmpty()) {
                continue;
            }

            foreach ($members as $member) {
                $member->notify(new WeeklyBusinessReport($business, [...$metrics, ...$funnel]));
            }

            $sent++;
        }

        return $sent;
    }
}
