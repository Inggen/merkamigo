<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\RegisterUser;
use App\Domain\Identity\Actions\ResolveUserByLogin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->create($request->all());

        $token = $user->createToken($request->userAgent() ?: 'api')->plainTextToken;

        return ApiResponse::response([
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'token' => $token,
        ], status: 201);
    }

    public function login(Request $request, ResolveUserByLogin $resolveUserByLogin): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $resolveUserByLogin->handle(
            (string) $request->string('login'),
            (string) $request->string('password'),
        );

        if (! $user) {
            return ApiError::response('Credenciales inválidas.', status: 401);
        }

        $token = $user->createToken($request->userAgent() ?: 'api')->plainTextToken;

        return ApiResponse::response([
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::response(message: 'Sesión cerrada.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::response($user->only(['id', 'name', 'email', 'phone']));
    }
}
