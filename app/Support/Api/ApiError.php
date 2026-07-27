<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

/**
 * Formato estándar de error para /api/v1 (0.4 del TODO):
 * {"error": {"code", "message", "details"}}.
 */
class ApiError
{
    /**
     * @param  array<string, mixed>  $details
     */
    public static function response(string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => self::codeForStatus($status),
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    private static function codeForStatus(int $status): string
    {
        return match (true) {
            $status === 401 => 'unauthenticated',
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 422 => 'validation_failed',
            $status === 429 => 'too_many_requests',
            $status >= 500 => 'server_error',
            default => 'bad_request',
        };
    }
}
