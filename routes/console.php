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

// IMM-043 (Fase 4 del TODO inmersivo): retención de eventos de plazas inmersivas.
Schedule::command('immersive-events:prune')->weekly();

// 3.1 del TODO: recordatorios de renovación de verificación.
Schedule::command('trust:remind-verification-expiry')->daily();

// 4.2 del TODO: cobra la renovación automática y marca en gracia a los
// que vencieron sin renovar — corre antes de la baja a Gratis de abajo.
Schedule::command('billing:process-subscription-renewals')->dailyAt('01:00');

// 4.1 del TODO: baja al plan Gratis tras vencer el periodo pagado o la gracia.
Schedule::command('billing:apply-plan-downgrades')->dailyAt('02:00');

// 4.5 del TODO: informe semanal por correo y alertas de vitrina sin completar.
Schedule::command('analytics:send-weekly-reports')->weekly();
Schedule::command('analytics:alert-incomplete-storefronts')->weekly();

Schedule::command('google-merchant:sync')
    ->hourly()
    ->withoutOverlapping()
    ->when(fn () => config('services.google_merchant.enabled'));
