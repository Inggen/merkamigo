<?php

use App\Http\Middleware\NegotiateMarkdown;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetPermissionsTeam;
use App\Support\Api\ApiError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(NegotiateMarkdown::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'business.team' => SetPermissionsTeam::class,
        ]);

        $middleware->throttleApi();

        // Pedido del usuario: el asistente del panel de emprendedor
        // (`/api/v1/asistente/chat`) necesita saber qué negocio tiene la
        // persona que ya inició sesión en el navegador — sin esto, las
        // rutas de `api.php` son sin estado (no leen la cookie de sesión),
        // así que `$request->user()` siempre daría null aunque haya
        // sesión iniciada. `statefulApi()` activa la autenticación por
        // cookie de Sanctum solo para los dominios de `config('sanctum.stateful')`
        // (ya incluye este dominio) — las peticiones externas con token
        // siguen funcionando igual, esto solo se suma como alternativa.
        $middleware->statefulApi();

        // Beacon de analítica pública sin sesión (1.8 del TODO: registrar
        // clic en compartir desde `navigator.sendBeacon`/`fetch`, sin poder
        // adjuntar un token CSRF). No muta datos sensibles ni de sesión;
        // `RegisterAnalyticsEvent` ya deduplica por visitante y hay
        // throttling en la ruta.
        $middleware->validateCsrfTokens(except: [
            'm/*/compartir',
            // Webhook de Wompi (4.2 del TODO): notificación servidor a
            // servidor, no puede adjuntar un token CSRF de sesión. Se
            // verifica con la firma propia de Wompi en el controlador.
            'webhooks/wompi',
        ]);

        // Preferencias de UI (Cliente/Emprendedor, municipio elegido), no
        // datos sensibles: sin cifrar para que sobrevivan sin fricción entre
        // invitado y registro (App\Domain\Identity\Actions\SwitchExperience,
        // App\Domain\Discovery\Actions\SetPreferredMunicipality).
        $middleware->encryptCookies(except: ['experience', 'municipio']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiError::response($e->getMessage(), $e->errors(), $e->status);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiError::response(
                    $e->getMessage() ?: 'Error',
                    [],
                    $e->getStatusCode(),
                );
            }
        });
    })->create();
