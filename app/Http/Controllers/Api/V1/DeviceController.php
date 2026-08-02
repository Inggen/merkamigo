<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\RegisterDevice;
use App\Domain\Identity\Actions\RevokeDevice;
use App\Domain\Identity\Models\UserDevice;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registro y revocación de dispositivos para notificaciones push (5.2
 * del TODO).
 */
class DeviceController extends Controller
{
    public function store(Request $request, RegisterDevice $registerDevice): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:fcm,apns,web'],
            'push_token' => ['required', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $device = $registerDevice->handle($request->user(), $data['platform'], $data['push_token'], $data['app_version'] ?? null);

        return ApiResponse::response(['id' => $device->id, 'platform' => $device->platform], status: 201);
    }

    public function destroy(Request $request, UserDevice $device, RevokeDevice $revokeDevice): JsonResponse
    {
        abort_unless($device->user_id === $request->user()->id, 403);

        $revokeDevice->handle($device);

        return ApiResponse::response(message: 'Dispositivo revocado.');
    }
}
