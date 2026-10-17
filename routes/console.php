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

// PR4 de TODO_VENTAS_RENTABILIDAD.md: renovación mensual de add-ons
// recurrentes (ej. asistente IA) — mismo horario que la renovación de
// planes, mismo motivo (tarjeta guardada del negocio).
Schedule::command('billing:process-entitlement-renewals')->dailyAt('01:30');

// 4.5 del TODO: informe semanal por correo y alertas de vitrina sin completar.
Schedule::command('analytics:send-weekly-reports')->weekly();
Schedule::command('analytics:alert-incomplete-storefronts')->weekly();

// TODO-Marketplace-Checkout.md: cobro semanal en lote de la comisión de
// Merkamigo sobre las ventas de marketplace (el pago del cliente ya está
// en la cuenta del negocio, esto es aparte, contra su tarjeta guardada).
Schedule::command('marketplace:charge-commissions')->weeklyOn(1, '03:00');

// Fase 8.2 del TODO social: cobra el primer periodo real de las
// suscripciones cuya prueba venció y renueva las que ya cumplieron su
// periodo pagado (dinero directo a la cuenta Wompi de cada negocio).
Schedule::command('subscriptions:renew')->dailyAt('04:00');

// Conciliación periódica de Google Merchant (Fase 10 de la integración):
// sin flags, el comando solo encola los productos elegibles que nunca se
// sincronizaron, quedaron en error/requiere_ajustes, o cambiaron desde su
// último envío exitoso — nunca reenvía todo el catálogo cada hora.
Schedule::command('google-merchant:sync')
    ->hourly()
    ->withoutOverlapping()
    ->when(fn () => config('services.google_merchant.enabled'));

// TODO_Merkapuntos.md, F1.6: libera puntos/stock/presupuesto de los canjes
// reservados que vencieron sin entrega. Cada 5 minutos porque la validez
// por defecto del código es de 15 (`config('loyalty.redemption_token_ttl_minutes')`).
Schedule::command('loyalty:expire-redemptions')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => config('loyalty.enabled'));

// TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3/4: libera la
// retención de las reservas de eventos que vencieron sin pago, mismo
// criterio de frecuencia que Merkapuntos (el "hold" por defecto es de
// minutos, no de horas).
Schedule::command('events:expire-reservations')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5: un evento
// publicado deja de aparecer como "próximo" cuando su fecha ya pasó.
Schedule::command('events:finalize-public-events')->hourly();

// Reservas de CUPO a eventos públicos (pedido del usuario, 2026-10-08) —
// mismo criterio de frecuencia que `events:expire-reservations`.
Schedule::command('events:expire-attendances')
    ->everyFiveMinutes()
    ->withoutOverlapping();
