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
        // Bug reportado por el usuario (2026-10-13): al "Entrar como" un
        // usuario desde el admin, la sesión se perdía al navegar a otra
        // sección en producción (nunca en local) — pedía autenticarse de
        // nuevo. Causa real: esta app no confiaba en NINGÚN proxy para
        // los headers `X-Forwarded-*`, así que si el hosting tiene
        // cualquier capa delante de PHP que termine el SSL (típico en
        // hosting compartido), Laravel ve la petición como HTTP interno
        // aunque el navegador use HTTPS. Con `session.secure` sin fijar
        // (`SESSION_SECURE_COOKIE` no está en el .env), Laravel decide el
        // flag `Secure` de la cookie según esa detección — si varía entre
        // peticiones, el navegador puede llegar a rechazar en silencio un
        // `Set-Cookie` sin `Secure` cuando ya tenía uno con `Secure`
        // puesto (así lo exige la propia especificación de cookies), y
        // justo eso es lo que pasa en `StartUserImpersonation` al
        // regenerar el ID de sesión (`session->migrate(true)`): el
        // navegador se queda con la cookie VIEJA, que para ese punto ya
        // fue invalidada en el servidor — de ahí que la siguiente página
        // se vea como si nunca hubiera iniciado sesión. `trustProxies('*')`
        // es la práctica estándar para este tipo de hosting, donde no se
        // conoce (ni es fija) la IP del proxy que hace la terminación SSL.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

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
