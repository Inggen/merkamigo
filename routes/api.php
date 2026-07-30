<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NeedController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('health', HealthController::class)->name('health');

    Route::middleware('throttle:6,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');

        Route::get('businesses/{business}', [BusinessController::class, 'show'])
            ->middleware('business.team')
            ->name('businesses.show');

        Route::post('needs', [NeedController::class, 'store'])->name('needs.store');
        Route::get('needs/{need}', [NeedController::class, 'show'])->name('needs.show');
        Route::patch('needs/{need}', [NeedController::class, 'update'])->name('needs.update');
    });
});
