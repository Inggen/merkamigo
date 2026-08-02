<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Http\Controllers\Controller;
use App\Http\Resources\NeedResource;
use App\Http\Resources\PublicNeedResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `POST/GET/PATCH /api/v1/needs` (2.1 del TODO). Reutiliza exactamente las
 * mismas acciones que el formulario web (`pages::pidelo.nueva`), sin
 * duplicar reglas (0.4 del TODO).
 */
class NeedController extends Controller
{
    /**
     * Catálogo público de necesidades abiertas (5.1 del TODO), mismo
     * criterio de visibilidad que `Need::scopeOpenIn()` ya usa en
     * `plaza.show` y en "Oportunidades" del Emprendedor — no reemplaza el
     * `show`/`update` de más abajo, que siguen siendo privados (solo el
     * dueño de la necesidad).
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'municipio_id' => ['required', 'integer', 'exists:municipalities,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $needs = Need::query()
            ->openIn($data['municipio_id'], $data['categoria_id'] ?? null)
            ->withCount('offers')
            ->with('category')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return ApiResponse::paginated($needs, PublicNeedResource::class);
    }

    public function store(Request $request, SaveNeedDraft $saveNeedDraft): JsonResponse
    {
        $need = $saveNeedDraft->handle($request->user(), null, $request->only([
            'title', 'description', 'municipality_id', 'category_id', 'zone', 'budget',
        ]));

        return ApiResponse::response(new NeedResource($need), status: 201);
    }

    public function show(Request $request, Need $need): JsonResponse
    {
        $this->authorize('view', $need);

        return ApiResponse::response(new NeedResource($need));
    }

    public function update(Request $request, Need $need, SaveNeedDraft $saveNeedDraft): JsonResponse
    {
        $this->authorize('update', $need);

        $need = $saveNeedDraft->handle($request->user(), $need, $request->only([
            'title', 'description', 'municipality_id', 'category_id', 'zone', 'budget',
        ]));

        return ApiResponse::response(new NeedResource($need));
    }
}
