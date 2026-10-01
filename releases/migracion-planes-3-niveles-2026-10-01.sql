-- Ejecutar en phpMyAdmin (o el gestor SQL de cPanel) sobre la base de datos
-- de producción de Merkamigo. Son datos, no esquema: no agrega columnas ni
-- toca `migrations`, solo actualiza/inserta filas en `plans`. Seguro de
-- correr más de una vez (UPDATE por `slug` + INSERT ... ON DUPLICATE KEY).
--
-- Qué arregla: la sección "Tu plan" (`⚡plan.blade.php`) ya lee `plans` en
-- vivo, así que mostraba el reparto viejo (2 planes, "Gratis", productos
-- ilimitados en Emprendedor) porque en producción la tabla nunca se
-- actualizó con el reparto nuevo de 3 niveles. Este script deja la tabla
-- igual a la base de datos local ya verificada.
--
-- Qué NO arregla: los candados nuevos de Copiloto, Cobros en línea,
-- Ventas, Asistente IA/Chatbot, En vivo y Métricas de 90 días/exportar CSV
-- viven en código PHP (`Business::isOnPaidPlan()`/`isOnTopPlan()`, cada
-- `⚡*.blade.php` correspondiente), no en esta tabla. Sin desplegar ese
-- código, cualquier negocio en producción sigue usando esas funciones sin
-- el límite de plan, sin importar qué diga esta tabla. Este script deja
-- los DATOS correctos; el CÓDIGO necesita su propio despliegue aparte.

-- 1) Básico (antes "Gratis"): mismo slug `gratis`, nombre y límites nuevos.
UPDATE `plans`
SET
    `name` = 'Básico',
    `limits` = JSON_OBJECT('max_products', 5, 'max_members', 1, 'max_featured_days', 0, 'max_storefronts', 1),
    `features` = JSON_ARRAY(
        'Vitrina pública en la Plaza',
        'Hasta 5 productos o servicios',
        '1 colaborador en el equipo',
        'Recibe y responde solicitudes de "Pídelo en Merkamigo"'
    )
WHERE `slug` = 'gratis';

-- 2) Emprendedor: deja de tener productos ilimitados y pierde el
--    asistente IA/chatbot (ahora exclusivo de Negocios). `price_cents` se
--    reafirma en $49.900 por si esta base de datos todavía tuviera el
--    valor de hipótesis viejo ($19.900).
UPDATE `plans`
SET
    `price_cents` = 4990000,
    `limits` = JSON_OBJECT('max_products', 20, 'max_members', 3, 'max_featured_days', 7, 'max_storefronts', 2),
    `features` = JSON_ARRAY(
        'Hasta 20 productos y servicios',
        'Hasta 2 vitrinas (negocios) en tu cuenta',
        'Hasta 3 colaboradores en el equipo',
        'Copiloto de WhatsApp para promociones',
        'Cobros en línea con tu propia cuenta Wompi',
        'Destacados en la Plaza hasta 7 días'
    )
WHERE `slug` = 'emprendedor';

-- 3) Negocios: plan nuevo, tercer nivel por encima de Emprendedor.
INSERT INTO `plans`
    (`slug`, `name`, `description`, `price_cents`, `billing_period`, `limits`, `features`, `trial_days`, `is_active`, `position`, `created_at`, `updated_at`)
VALUES (
    'negocios',
    'Negocios',
    'Para quien maneja varios negocios o necesita todas las herramientas de Merkamigo.',
    9900000,
    'mensual',
    JSON_OBJECT('max_products', 50, 'max_members', 5, 'max_featured_days', 15, 'max_storefronts', 5),
    JSON_ARRAY(
        'Hasta 50 productos y servicios',
        'Hasta 5 vitrinas (negocios) en tu cuenta',
        'Hasta 5 colaboradores en el equipo',
        'Asistente IA y chatbot en tu vitrina',
        'Transmisiones En vivo (Live Commerce)',
        'Métricas avanzadas: 90 días y exportar CSV',
        'Destacados en la Plaza hasta 15 días'
    ),
    14,
    1,
    2,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `price_cents` = VALUES(`price_cents`),
    `limits` = VALUES(`limits`),
    `features` = VALUES(`features`),
    `trial_days` = VALUES(`trial_days`),
    `is_active` = VALUES(`is_active`),
    `position` = VALUES(`position`),
    `updated_at` = NOW();

-- 4) Verificación rápida: deben verse las 3 filas con los valores de
--    arriba. Si falta alguna o los límites no coinciden, algo falló.
SELECT `slug`, `name`, `price_cents`, `limits`, `features`, `position` FROM `plans` ORDER BY `position`;
