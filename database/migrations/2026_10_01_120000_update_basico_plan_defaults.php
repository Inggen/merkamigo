<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La migración original (`2026_07_31_134135_create_plans_table`) sembró el
 * plan "Gratis" con datos que hoy son incorrectos (10 productos,
 * colaboradores ilimitados) — ese valor se volvió el reparto real tras el
 * cambio a 3 planes del 2026-09-30/10-01 (Básico/Emprendedor/Negocios), ver
 * `database/seeders/PlanSeeder.php`. Se corrige aquí, no editando la
 * migración original, por el mismo motivo que
 * `2026_08_01_130000_add_max_storefronts_to_plans_limits`: cualquier base
 * de datos de prueba que corre migraciones sin seeders (`RefreshDatabase`)
 * depende de que esta fila siga existiendo con el valor correcto — sin
 * esto, la tarjeta "Miembros del equipo" mostraba "Sin límite" en vez del
 * tope real, y nunca ofrecía "Mejorar plan" al llegar al tope (bug
 * reportado por el usuario el 2026-10-01).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('slug', 'gratis')->update([
            'name' => 'Básico',
            'limits' => json_encode([
                'max_products' => 5,
                'max_members' => 1,
                'max_featured_days' => 0,
                'max_storefronts' => 1,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 5 productos o servicios',
                '1 colaborador en el equipo',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'gratis')->update([
            'name' => 'Gratis',
            'limits' => json_encode([
                'max_products' => 10,
                'max_members' => null,
                'max_featured_days' => 0,
                'max_storefronts' => 1,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 10 productos o servicios',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
            ]),
        ]);
    }
};
