<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Http\Controllers\Controller;
use App\Http\Resources\NeedResource;
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
