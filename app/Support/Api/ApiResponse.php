<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

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
}
