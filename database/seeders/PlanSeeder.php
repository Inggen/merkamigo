<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes iniciales (4.1 del TODO). Precios de referencia previa validados
 * como hipótesis en 0.1 ("Perfil gratuito" y "Plan Emprendedor $19.900
 * COP/mes") — no codificados en la aplicación, solo sembrados aquí como
 * valor de arranque editable desde Filament.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['slug' => 'gratis'],
            [
                'name' => 'Gratis',
                'description' => 'Vitrina básica para empezar a vender en Merkamigo.',
                'price_cents' => null,
                'billing_period' => Plan::MENSUAL,
                'limits' => [
                    'max_products' => 10,
                    'max_members' => null,
                    'max_featured_days' => 0,
                    'max_storefronts' => 1,
                ],
                'features' => [
                    'Vitrina pública en la Plaza',
                    'Hasta 10 productos o servicios',
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
                'price_cents' => 1990000,
                'billing_period' => Plan::MENSUAL,
                'limits' => [
                    'max_products' => null,
                    'max_members' => 5,
                    'max_featured_days' => 7,
                    'max_storefronts' => 3,
                ],
                'features' => [
                    'Productos y servicios ilimitados',
                    'Hasta 5 colaboradores en el equipo',
                    'Destacados en la Plaza hasta 7 días',
                    'Copiloto de WhatsApp para promociones',
                    'Asistente con IA en tu vitrina',
                ],
                'trial_days' => 14,
                'is_active' => true,
                'position' => 1,
            ],
        );
    }
}
