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
                    'max_products' => 5,
                    'max_members' => 1,
                    'max_featured_days' => 0,
                    'max_storefronts' => 1,
                ],
                'features' => [
                    'Vitrina pública en la Plaza',
                    'Hasta 5 productos o servicios',
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
                    'max_products' => 20,
                    'max_members' => 3,
                    'max_featured_days' => 7,
                    'max_storefronts' => 2,
                ],
                'features' => [
                    'Hasta 20 productos y servicios',
                    'Hasta 2 vitrinas (negocios) en tu cuenta',
                    'Hasta 3 colaboradores en el equipo',
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
                    'max_products' => 50,
                    'max_members' => 5,
                    'max_featured_days' => 15,
                    'max_storefronts' => 5,
                ],
                'features' => [
                    'Hasta 50 productos y servicios',
                    'Hasta 5 vitrinas (negocios) en tu cuenta',
                    'Hasta 5 colaboradores en el equipo',
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
