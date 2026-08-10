<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandSlot;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * IMM-020b (puente mínimo de stands dinámicos): la única fuente pública de
 * "qué stand está realmente ocupado, dónde y con qué apariencia" para una
 * plaza — consumida por `public/js/lib/dynamic-stand-loader.js` desde las
 * escenas fijas por municipio (decisión de arquitectura #1 del TODO
 * inmersivo). La geometría fija de la plaza sigue siendo código; esto solo
 * resuelve la capa dinámica de stands (decisión #2).
 */
class ImmersivePlazaStandsController extends Controller
{
    public function index(ImmersivePlaza $plaza): JsonResponse
    {
        $stands = $plaza->slots()
            ->with(['assignment.template'])
            ->get()
            ->filter(fn (StandSlot $slot): bool => (bool) $slot->assignment?->isLive())
            ->map(fn (StandSlot $slot) => [
                'slot_code' => $slot->code,
                'world_position' => $slot->world_position,
                'rotation' => $slot->rotation,
                // Orden de prioridad de renderizado (IMM-020b): GLB real >
                // definición generada por IA > forma voxel procedural. El
                // consumidor (dynamic-stand-loader.js) decide cuál usar
                // según cuáles de estos campos vengan poblados.
                'model_url' => $slot->assignment->template?->modelPathUrl(),
                'builder_key' => $slot->assignment->template?->builder_key,
                'model_definition' => $slot->assignment->template?->model_definition,
                'scale' => self::scaleForSlot($slot, $slot->assignment->template),
            ])
            ->values();

        return ApiResponse::response($stands);
    }

    /**
     * Sin esto, un stand SIEMPRE se dibuja al tamaño fijo con el que se
     * diseñó su plantilla (`builderKey`/`modelDefinition`/GLB), sin
     * importar el ancho/profundidad real configurado en el slot que
     * ocupa — un slot grande con una plantilla pequeña se veía diminuto.
     *
     * Escala UNIFORME (mismo factor en X, Y y Z) — escalar cada eje por
     * separado (como sí hace `PlazaSpatialEditor::scaleVectorFromSize()`
     * para props, donde el admin ajusta cada eje a mano) deformaba GLBs
     * reales al estirar solo X/Z sin tocar Y. Se usa la MENOR de las dos
     * proporciones (ancho, profundidad) para que la plantilla crezca o
     * encoja pareja sin desbordar el slot en ningún eje horizontal.
     *
     * @return array{x: float, y: float, z: float}
     */
    private static function scaleForSlot(StandSlot $slot, ?ImmersiveObjectTemplate $template): array
    {
        if (! $template || $template->max_width <= 0 || $template->max_depth <= 0) {
            return ['x' => 1.0, 'y' => 1.0, 'z' => 1.0];
        }

        $widthRatio = $slot->max_width / $template->max_width;
        $depthRatio = $slot->max_depth / $template->max_depth;
        $scale = round(min($widthRatio, $depthRatio), 4);

        return ['x' => $scale, 'y' => $scale, 'z' => $scale];
    }
}
