<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Interruptor global de Merkapuntos (TODO_Merkapuntos.md, F0.3)
    |---------------------------------------------------------------------------
    |
    | El programa de recompensas está apagado por defecto en producción.
    | La activación real es selectiva por negocio vía `LoyaltyEnrollment`
    | (adhesión voluntaria con política aprobada) — este flag es el
    | interruptor de emergencia que apaga TODO el programa a la vez sin
    | tocar las adhesiones individuales de cada negocio.
    |
    */

    'enabled' => env('LOYALTY_ENABLED', false),

    /*
    |---------------------------------------------------------------------------
    | Validez por defecto del código de canje
    |---------------------------------------------------------------------------
    |
    | F1.8 / reglas de producto: "Validez inicial propuesta de código: 15
    | minutos, configurable y comunicada. No confundir con vencimiento de
    | los puntos." Esto NO es vencimiento de puntos (eso sigue desactivado
    | hasta aprobar una política explícita, ver `eligible_points_expiry`).
    |
    */

    'redemption_token_ttl_minutes' => env('LOYALTY_REDEMPTION_TTL_MINUTES', 15),

    /*
    |---------------------------------------------------------------------------
    | Vencimiento de puntos
    |---------------------------------------------------------------------------
    |
    | Reglas de producto: "Vencimiento de puntos queda desactivado hasta
    | aprobar una política explícita." No hay código que calcule
    | vencimiento de puntos todavía — este flag existe solo para dejar la
    | decisión visible, no para ser consultado por ninguna acción aún.
    |
    */

    'points_expiry_enabled' => false,

    /*
    |---------------------------------------------------------------------------
    | Solicitud excepcional con recibo
    |---------------------------------------------------------------------------
    |
    | F2.7: límites de carga del recibo privado.
    |
    */

    'receipt_claims' => [
        'max_upload_kb' => env('LOYALTY_RECEIPT_MAX_UPLOAD_KB', 5120),
        'max_age_days' => env('LOYALTY_RECEIPT_MAX_AGE_DAYS', 30),
    ],

    /*
    |---------------------------------------------------------------------------
    | Asistente de recompensa (TODO_Correccion_Logica_Merkapuntos.md)
    |---------------------------------------------------------------------------
    |
    | La sugerencia de puntos de una recompensa NUNCA se calcula desde el
    | costo del premio directamente — se calcula desde el ticket promedio
    | del negocio × cuántas compras quiere que haga el cliente antes de
    | alcanzarla (ver `MerkapuntosRewardCalculator`). Estos umbrales son
    | solo de ADVERTENCIA (nunca bloquean guardar) y deben quedar aquí
    | centralizados, no "quemados" en el servicio ni en la vista.
    |
    */

    'reward_assistant' => [
        // Las tres opciones que ve el comerciante: "más atractivo",
        // "equilibrado/recomendado" y "mayor protección del margen".
        'target_purchase_options' => [4, 6, 8],

        // Más de este número de compras equivalentes → "difícil de alcanzar".
        'max_recommended_purchases' => 12,

        // Más de este % del gasto objetivo → "costo alto para el negocio".
        'max_recommended_percentage' => 12.0,

        // El ticket promedio se calcula AUTOMÁTICAMENTE desde el
        // historial de compras del negocio ("así es muy difícil para
        // el emprendedor" — pedido del usuario, no se le debe pedir que
        // lo escriba a mano). Con menos compras registradas que esto,
        // el promedio no es confiable todavía y se le pide un estimado
        // manual solo como última opción.
        'min_purchases_for_average_ticket' => env('LOYALTY_MIN_PURCHASES_FOR_AVERAGE_TICKET', 3),
    ],

];
