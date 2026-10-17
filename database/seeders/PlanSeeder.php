<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes iniciales (4.1 del TODO) — no codificados en la aplicación, solo
 * sembrados aquí como valor editable desde Filament.
 *
 * Reparto de funciones por plan (2026-09-30, tabla del usuario):
 * Emprendedor deja de incluir el asistente IA / chatbot y el destacado
 * "En vivo" — quedan exclusivos de Negocios, igual que las métricas
 * avanzadas (90 días + exportar CSV). Copiloto de WhatsApp, cobros en
 * línea propios y ver ventas pasan a requerir un plan de pago (antes
 * abiertos a cualquiera). El plan Gratis se renombra a "Básico" y gana 1
 * colaborador (antes 0). Cualquier código que necesite distinguir "plan de
 * pago" de "plan gratuito" debe usar `Business::isOnPaidPlan()`, y el que
 * necesite "solo el plan más alto" debe usar `Business::isOnTopPlan()` —
 * nunca comparar `activePlan()->slug` a mano.
 *
 * Actualización (2026-10-13, pedido del usuario): Merkapuntos pasa a
 * requerir un plan de pago (Emprendedor o Negocios) y Eventos pasa a ser
 * exclusivo de Negocios — ver el `abort_unless` correspondiente en
 * `⚡merkapuntos.blade.php` y `⚡eventos.blade.php`. El listado de
 * `features` de cada plan ahora es ACUMULATIVO (Emprendedor repite todo
 * lo de Básico, Negocios repite todo lo de Emprendedor) para que la
 * página de comparar planes se lea como niveles reales, no como extras
 * sueltos — ver también la migración `2026_10_13_120000_add_
 * merkapuntos_and_eventos_to_plan_features` para las bases de datos que
 * ya tenían estas filas sembradas.
 *
 * Actualización (2026-10-14, pedido del usuario): tope de productos/
 * servicios más bajo por plan — Básico 5→3, Emprendedor 20→15, Negocios
 * 50→40 — ver la migración `2026_10_14_120000_lower_plan_product_
 * limits` para las bases de datos que ya tenían estas filas sembradas.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['slug' => 'gratis'],
            [
                'name' => 'Básico',
                'description' => 'Vitrina básica para empezar a vender en Merkamigo.',
                'price_cents' => null,
                'billing_period' => Plan::MENSUAL,
                'limits' => [
                    'max_products' => 3,
                    'max_members' => 1,
                    'max_featured_days' => 0,
                    'max_storefronts' => 1,
                ],
                'features' => [
                    'Vitrina pública en la Plaza',
                    'Hasta 3 productos o servicios',
                    '1 colaborador en el equipo',
                    'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
                ],
                'trial_days' => 0,
                'is_active' => true,
                'position' => 0,
            ],
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'emprendedor'],
            [
                'name' => 'Emprendedor',
                'description' => 'Más productos, colaboradores y destacados para hacer crecer tu negocio.',
                'price_cents' => 4990000,
                'billing_period' => Plan::MENSUAL,
                'limits' => [
                    'max_products' => 15,
                    'max_members' => 3,
                    'max_featured_days' => 7,
                    'max_storefronts' => 2,
                ],
                'features' => [
                    'Vitrina pública en la Plaza',
                    'Hasta 15 productos y servicios',
                    'Hasta 2 vitrinas (negocios) en tu cuenta',
                    'Hasta 3 colaboradores en el equipo',
                    'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
                    'Merkapuntos: premios y fidelización de clientes',
                    'Copiloto de WhatsApp para promociones',
                    'Cobros en línea con tu propia cuenta Wompi',
                    'Destacados en la Plaza hasta 7 días',
                ],
                'trial_days' => 14,
                'is_active' => true,
                'position' => 1,
            ],
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'negocios'],
            [
                'name' => 'Negocios',
                'description' => 'Para quien maneja varios negocios o necesita todas las herramientas de Merkamigo.',
                'price_cents' => 9900000,
                'billing_period' => Plan::MENSUAL,
                'limits' => [
                    'max_products' => 40,
                    'max_members' => 5,
                    'max_featured_days' => 15,
                    'max_storefronts' => 5,
                ],
                'features' => [
                    'Vitrina pública en la Plaza',
                    'Hasta 40 productos y servicios',
                    'Hasta 5 vitrinas (negocios) en tu cuenta',
                    'Hasta 5 colaboradores en el equipo',
                    'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
                    'Merkapuntos: premios y fidelización de clientes',
                    'Copiloto de WhatsApp para promociones',
                    'Cobros en línea con tu propia cuenta Wompi',
                    'Eventos: agenda pública y reservas',
                    'Asistente IA y chatbot en tu vitrina',
                    'Transmisiones En vivo (Live Commerce)',
                    'Métricas avanzadas: 90 días y exportar CSV',
                    'Destacados en la Plaza hasta 15 días',
                ],
                'trial_days' => 14,
                'is_active' => true,
                'position' => 2,
            ],
        );
    }
}
