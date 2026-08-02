<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formato estándar de respuesta exitosa para /api/v1 (0.4 del TODO).
 */
class ApiResponse
{
    public static function response(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    /**
     * Envelope estándar para listas paginadas (5.1 del TODO): mismo `data`
     * de siempre (el resource por elemento), más `meta`/`links` con la
     * información de paginación, sin depender del formato por defecto de
     * `JsonResource::collection()->response()` para no romper el contrato
     * `{"data": ...}` ya documentado en `docs/architecture/versionado-api.md`.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  LengthAwarePaginator<array-key, TModel>  $paginator
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
