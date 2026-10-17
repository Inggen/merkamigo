<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pedido del usuario (2026-10-14): tope de productos/servicios por plan
 * más bajo — Básico 5→3, Emprendedor 20→15, Negocios 50→40. Igual que
 * `2026_10_01_120000_update_basico_plan_defaults` y
 * `2026_10_13_120000_add_merkapuntos_and_eventos_to_plan_features`, se
 * corrige con una migración nueva (no editando las originales) para que
 * cualquier base de datos que ya tenía estas filas sembradas —incluida
 * producción— quede con el valor correcto, y el texto de `features` que
 * menciona el tope quede en sincronía con el límite real.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('slug', 'gratis')->update([
            'limits' => json_encode([
                'max_products' => 3,
                'max_members' => 1,
                'max_featured_days' => 0,
                'max_storefronts' => 1,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 3 productos o servicios',
                '1 colaborador en el equipo',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
            ]),
        ]);

        DB::table('plans')->where('slug', 'emprendedor')->update([
            'limits' => json_encode([
                'max_products' => 15,
                'max_members' => 3,
                'max_featured_days' => 7,
                'max_storefronts' => 2,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 15 productos y servicios',
                'Hasta 2 vitrinas (negocios) en tu cuenta',
                'Hasta 3 colaboradores en el equipo',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
                'Merkapuntos: premios y fidelización de clientes',
                'Copiloto de WhatsApp para promociones',
                'Cobros en línea con tu propia cuenta Wompi',
                'Destacados en la Plaza hasta 7 días',
            ]),
        ]);

        DB::table('plans')->where('slug', 'negocios')->update([
            'limits' => json_encode([
                'max_products' => 40,
                'max_members' => 5,
                'max_featured_days' => 15,
                'max_storefronts' => 5,
            ]),
            'features' => json_encode([
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
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'gratis')->update([
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

        DB::table('plans')->where('slug', 'emprendedor')->update([
            'limits' => json_encode([
                'max_products' => 20,
                'max_members' => 3,
                'max_featured_days' => 7,
                'max_storefronts' => 2,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 20 productos y servicios',
                'Hasta 2 vitrinas (negocios) en tu cuenta',
                'Hasta 3 colaboradores en el equipo',
                'Recibe y responde solicitudes de "Pídelo en Merkamigo"',
                'Merkapuntos: premios y fidelización de clientes',
                'Copiloto de WhatsApp para promociones',
                'Cobros en línea con tu propia cuenta Wompi',
                'Destacados en la Plaza hasta 7 días',
            ]),
        ]);

        DB::table('plans')->where('slug', 'negocios')->update([
            'limits' => json_encode([
                'max_products' => 50,
                'max_members' => 5,
                'max_featured_days' => 15,
                'max_storefronts' => 5,
            ]),
            'features' => json_encode([
                'Vitrina pública en la Plaza',
                'Hasta 50 productos y servicios',
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
            ]),
        ]);
    }
};
