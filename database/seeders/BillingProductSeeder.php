<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\BillingProduct;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de productos de ingreso complementario (4.3 del TODO):
 * precios de referencia, editables desde Filament, no codificados en la
 * aplicación.
 */
class BillingProductSeeder extends Seeder
{
    public function run(): void
    {
        BillingProduct::query()->updateOrCreate(
            ['slug' => 'destacado-7'],
            [
                'name' => 'Destacado 7 días',
                'description' => 'Tu vitrina aparece primero en la Plaza de tu municipio durante 7 días.',
                'price_cents' => 990000,
                'kind' => BillingProduct::DESTACADO,
                'payload' => ['days' => 7],
                'is_active' => true,
            ],
        );

        BillingProduct::query()->updateOrCreate(
            ['slug' => 'destacado-14'],
            [
                'name' => 'Destacado 14 días',
                'description' => 'Tu vitrina aparece primero en la Plaza de tu municipio durante 14 días.',
                'price_cents' => 1690000,
                'kind' => BillingProduct::DESTACADO,
                'payload' => ['days' => 14],
                'is_active' => true,
            ],
        );

        BillingProduct::query()->updateOrCreate(
            ['slug' => 'destacado-30'],
            [
                'name' => 'Destacado 30 días',
                'description' => 'Tu vitrina aparece primero en la Plaza de tu municipio durante 30 días.',
                'price_cents' => 2990000,
                'kind' => BillingProduct::DESTACADO,
                'payload' => ['days' => 30],
                'is_active' => true,
            ],
        );

        BillingProduct::query()->updateOrCreate(
            ['slug' => 'vitrina-asistida'],
            [
                'name' => 'Vitrina asistida',
                'description' => 'Nuestro equipo te ayuda a completar y pulir tu vitrina (fotos, descripciones, categorías).',
                'price_cents' => 4990000,
                'kind' => BillingProduct::VITRINA_ASISTIDA,
                'payload' => null,
                'is_active' => true,
            ],
        );

        BillingProduct::query()->updateOrCreate(
            ['slug' => 'kit-arranca-bonito'],
            [
                'name' => 'Kit Arranca Bonito',
                'description' => 'Sesión de fotos básica + vitrina asistida + destacado de 14 días para arrancar con todo.',
                'price_cents' => 9990000,
                'kind' => BillingProduct::KIT_ARRANCA_BONITO,
                'payload' => ['days' => 14],
                'is_active' => true,
            ],
        );
    }
}
