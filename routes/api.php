<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\BusinessVerificationController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DiscoveryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MetricsController;
use App\Http\Controllers\Api\V1\NeedController;
use App\Http\Controllers\Api\V1\NotificationPreferencesController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\OrderConfirmationController;
use App\Http\Controllers\Api\V1\WhatsAppContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('health', HealthController::class)->name('health');

    // Descubrimiento público (5.1 del TODO): mismo contenido ya público en
    // PlazaController/VitrinaController, sin autenticación.
    Route::get('municipios', [DiscoveryController::class, 'municipios'])->name('municipios');
    Route::get('categorias', [DiscoveryController::class, 'categorias'])->name('categorias');
    Route::get('plaza', [DiscoveryController::class, 'plaza'])->name('plaza');
    Route::get('plaza/negocios/{business:slug}', [DiscoveryController::class, 'business'])->name('plaza.negocios.show');
    Route::get('plaza/negocios/{business:slug}/productos', [DiscoveryController::class, 'products'])->name('plaza.negocios.productos');
    Route::get('plaza/negocios/{business:slug}/productos/{product}', [DiscoveryController::class, 'product'])->name('plaza.negocios.productos.show');

    // Catálogo público de necesidades abiertas (5.1/2.1 del TODO) — el
    // resto de `needs.*` sigue privado, ver dentro del grupo `auth:sanctum`.
    Route::get('needs', [NeedController::class, 'index'])->name('needs.index');

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

        Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');

        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::patch('notificaciones/preferencias', [NotificationPreferencesController::class, 'update'])->name('notificaciones.preferencias');

        Route::get('order-confirmations', [OrderConfirmationController::class, 'index'])->name('order-confirmations.index');
        Route::post('order-confirmations/{orderConfirmation}/confirmar', [OrderConfirmationController::class, 'confirm'])->name('order-confirmations.confirmar');
        Route::post('order-confirmations/{orderConfirmation}/completar', [OrderConfirmationController::class, 'complete'])->name('order-confirmations.completar');
        Route::post('order-confirmations/{orderConfirmation}/disputar', [OrderConfirmationController::class, 'dispute'])->name('order-confirmations.disputar');
        Route::post('order-confirmations/{orderConfirmation}/cancelar', [OrderConfirmationController::class, 'cancel'])->name('order-confirmations.cancelar');
        Route::post('order-confirmations/{orderConfirmation}/recomendacion', [OrderConfirmationController::class, 'recommend'])->name('order-confirmations.recomendacion');

        Route::middleware('business.team')->prefix('businesses/{business}')->name('businesses.')->group(function () {
            Route::post('needs/{need}/offers', [OfferController::class, 'store'])->name('needs.offers.store');
            Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
            Route::post('verificacion', [BusinessVerificationController::class, 'store'])->name('verificacion.store');
            Route::get('metricas', [MetricsController::class, 'show'])->name('metricas.show');
            Route::get('whatsapp-contents', [WhatsAppContentController::class, 'index'])->name('whatsapp-contents.index');
            Route::post('whatsapp-contents/generar', [WhatsAppContentController::class, 'generate'])->name('whatsapp-contents.generar');
            Route::post('whatsapp-contents', [WhatsAppContentController::class, 'store'])->name('whatsapp-contents.store');
        });
    });
});
