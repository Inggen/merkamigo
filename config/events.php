<?php

// TODO_desarrollo_sistema_eventos_Merkamigo.md, "Decisiones de producto
// para arrancar": la prioridad por plan en la agenda es una PROPUESTA
// pendiente de aprobación comercial, no una decisión ya cerrada — vive
// en config (ajustable sin tocar código ni re-desplegar lógica) en vez
// de un valor fijo en `RankPublicEventsForAgenda`. Un plan sin entrada
// aquí cae al peso mínimo (ver `RankPublicEventsForAgenda::planWeight()`).

return [
    'plan_weights' => [
        'negocios' => 3,
        'emprendedor' => 2,
        'gratis' => 1,
    ],

    // Fase 7: "Activar por bandera en un piloto con Kebero; observar
    // errores, reservas abandonadas, pagos y métricas. Desplegar al
    // resto tras validar resultados." Con `pilot_mode` en true, solo los
    // negocios listados en `pilot_business_ids` pueden ACTIVAR el
    // módulo de eventos (`UpdateEventSettings`) — el resto puede seguir
    // viendo/explorando la agenda pública y sus botones de panel, pero
    // no encender `enabled`. Apagar `pilot_mode` (o vaciar la lista)
    // abre el módulo a todos sin tocar código.
    'pilot_mode' => env('EVENTS_PILOT_MODE', false),

    'pilot_business_ids' => array_filter(array_map(
        'intval',
        explode(',', (string) env('EVENTS_PILOT_BUSINESS_IDS', '')),
    )),

    // Pedido del usuario (2026-10-08): reserva de CUPO a un evento
    // público (distinta de la reserva de espacio). Minutos que se
    // retiene el cupo de una reserva de asistencia DE PAGO mientras se
    // completa el pago — mismo espíritu que `event_settings.hold_minutes`
    // para reservas de espacio, pero aquí es global porque no depende de
    // la configuración de un negocio en particular.
    'attendance_hold_minutes' => 30,
];
