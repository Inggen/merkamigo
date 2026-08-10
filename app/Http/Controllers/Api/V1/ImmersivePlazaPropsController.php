<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\ImmersivePlazaProp;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hermano de `ImmersivePlazaStandsController`: la fuente pública de "qué
 * elementos (construcciones, árboles, fuentes, monumentos, personajes)
 * tiene realmente una plaza y con qué apariencia" — consumida por
 * `dynamic-stand-loader.js` desde cualquier escena, incluida la genérica
 * (`generic-plaza-immersive.js`), que arma el mundo caminable completo a
 * partir de estos datos en vez de un script escrito a mano.
 */
class ImmersivePlazaPropsController extends Controller
{
    public function index(ImmersivePlaza $plaza, Request $request): JsonResponse
    {
        $props = $plaza->props()
            ->when(
                ! $this->canPreviewDraftProps($request),
                fn ($query) => $query->where('status', 'confirmado')
            )
            ->with('template')
            ->get()
            ->map(fn (ImmersivePlazaProp $prop) => [
                'world_position' => $prop->world_position,
                'rotation' => $prop->rotation,
                'scale' => $prop->scaleVector(),
                'collision_enabled' => (bool) $prop->collision_enabled,
                // Misma prioridad de renderizado que los stands (IMM-020b):
                // GLB real > definición generada por IA > forma voxel.
                'model_url' => $prop->template?->modelPathUrl(),
                'builder_key' => $prop->template?->builder_key,
                'model_definition' => $prop->template?->model_definition,
            ])
            ->values();

        return ApiResponse::response($props);
    }

    private function canPreviewDraftProps(Request $request): bool
    {
        return $request->boolean('preview')
            && ($request->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false);
    }
}
