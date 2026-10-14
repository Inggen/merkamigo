<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pedido del usuario (2026-10-13): la comparación de planes mostraba cada
 * plan como una lista de "extras" sobre el anterior, no como lo que
 * realmente es — un nivel que incluye TODO lo del nivel de abajo. Además,
 * el listado nunca mencionaba Merkapuntos ni Eventos, aunque ambos
 * paneles eran accesibles desde cualquier plan (sin ninguna restricción
 * real, ver `abort_unless` agregado en `⚡merkapuntos.blade.php` y
 * `⚡eventos.blade.php`). Reparto definitivo: Merkapuntos pasa a requerir
 * un plan de pago (Emprendedor o Negocios — `Business::isOnPaidPlan()`),
 * Eventos pasa a ser exclusivo de Negocios (`Business::isOnTopPlan()`).
 * Esta migración deja el `features` de cada plan ya sembrado como la
 * lista acumulada completa, igual que `2026_10_01_120000_update_basico_
 * plan_defaults` hizo con "Básico" — necesaria para cualquier base de
 * datos que ya tenía estas filas sembradas (incluida producción).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('slug', 'emprendedor')->update([
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

    public function down(): void
    {
        DB::table('plans')->where('slug', 'emprendedor')->update([
            'features' => json_encode([
                'Hasta 20 productos y servicios',
                'Hasta 2 vitrinas (negocios) en tu cuenta',
                'Hasta 3 colaboradores en el equipo',
                'Copiloto de WhatsApp para promociones',
                'Cobros en línea con tu propia cuenta Wompi',
                'Destacados en la Plaza hasta 7 días',
            ]),
        ]);

        DB::table('plans')->where('slug', 'negocios')->update([
            'features' => json_encode([
                'Hasta 50 productos y servicios',
                'Hasta 5 vitrinas (negocios) en tu cuenta',
                'Hasta 5 colaboradores en el equipo',
                'Asistente IA y chatbot en tu vitrina',
                'Transmisiones En vivo (Live Commerce)',
                'Métricas avanzadas: 90 días y exportar CSV',
                'Destacados en la Plaza hasta 15 días',
            ]),
        ]);
    }
};
