<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 2.1/2.3 del TODO: cerrar por vencimiento las necesidades publicadas.
Schedule::command('needs:expire-overdue')->daily();

// 0.6 del TODO: retención de eventos analíticos.
Schedule::command('analytics:prune-events')->weekly();

// 3.1 del TODO: recordatorios de renovación de verificación.
Schedule::command('trust:remind-verification-expiry')->daily();

// 4.1 del TODO: baja al plan Gratis tras vencer el periodo pagado.
Schedule::command('billing:apply-plan-downgrades')->daily();

// 4.5 del TODO: informe semanal por correo y alertas de vitrina sin completar.
Schedule::command('analytics:send-weekly-reports')->weekly();
Schedule::command('analytics:alert-incomplete-storefronts')->weekly();
