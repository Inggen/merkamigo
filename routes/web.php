<?php

use App\Http\Controllers\AgentDiscoveryController;
use App\Http\Controllers\Analytics\MetricsExportController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\PaymentSourceController;
use App\Http\Controllers\Billing\WompiWebhookController;
use App\Http\Controllers\BusinessVerificationDocumentController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmprendedoresController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\GoogleMerchantFeedController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\NeedsController;
use App\Http\Controllers\PlazaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VitrinaController;
use App\Http\Middleware\AddAgentDiscoveryHeaders;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

Route::middleware('auth')->group(function () {
    Route::get('/limpiar-cache', function () {
        abort_unless(auth()->user()?->hasAnyPlatformRole(['superadmin']), 403);

        config([
            'cache.default' => 'file',
            'queue.default' => 'database',
        ]);

        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'event:clear',
            'clear-compiled',
        ];

        $output = [];

        foreach ($commands as $command) {
            Artisan::call($command);

            $output[] = '$ php artisan '.$command;
            $output[] = trim(Artisan::output()) ?: 'OK';
            $output[] = '';
        }

        return '<pre>'.e(implode(PHP_EOL, $output)).'</pre>';
    });

    Route::get('/link', function () {
        abort_unless(auth()->user()?->hasAnyPlatformRole(['superadmin']), 403);

        Artisan::call('storage:link');

        return nl2br(e(Artisan::output()));
    });

    Route::get('/migrar', function () {
        abort_unless(auth()->user()?->hasAnyPlatformRole(['superadmin']), 403);

        Artisan::call('migrate', ['--force' => true]);

        return nl2br(e(Artisan::output()));
    });
});

Route::middleware(AddAgentDiscoveryHeaders::class)->group(function () {
    Route::get('/', [ClientesController::class, 'home'])->name('home');
    Route::get('clientes', [ClientesController::class, 'home'])->name('clientes.home');
    Route::get('.well-known/api-catalog', [AgentDiscoveryController::class, 'catalog'])->name('well-known.api-catalog');
    Route::get('docs/api', [AgentDiscoveryController::class, 'openApi'])->name('docs.api');
    Route::get('docs/api/reference', [AgentDiscoveryController::class, 'documentation'])->name('docs.api.reference');
});
Route::get('feeds/google-merchant.xml', [GoogleMerchantFeedController::class, 'index'])->name('feeds.google-merchant');

Route::view('terminos', 'legal.terminos')->name('terminos');
Route::view('privacidad', 'legal.privacidad')->name('privacidad');
Route::view('reglas-comunidad', 'legal.reglas-comunidad')->name('reglas-comunidad');
Route::view('como-funciona', 'public.como-funciona')->name('como-funciona');
Route::view('soporte', 'public.soporte')->name('soporte');
Route::get('soporte/solicitud', [SupportTicketController::class, 'create'])->name('soporte.solicitud.crear');
Route::post('soporte/solicitud', [SupportTicketController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('soporte.solicitud.guardar');
Route::view('preguntas-frecuentes', 'public.preguntas-frecuentes', ['faqs' => config('faq.preguntas')])
    ->name('preguntas-frecuentes');
Route::get('exp/plaza/{municipio:slug}', [PlazaController::class, 'genericPlaza'])->name('labs.generic-plaza');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Wompi (4.2 del TODO): retorno del checkout y webhook, ambos públicos —
// el webhook se verifica por firma propia, no por sesión.
Route::get('billing/checkout/retorno', [CheckoutController::class, 'return'])->name('billing.checkout.return');
Route::post('webhooks/wompi', [WompiWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.wompi');

Route::get('emprendedores/bienvenida', [EmprendedoresController::class, 'bienvenida'])
    ->name('emprendedores.bienvenida');

Route::post('experience', [ExperienceController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('experience.update');

Route::middleware('guest')->group(function () {
    Route::get('ingresar', fn () => view('pages::auth.login'))->name('login');
    Route::post('ingresar', [AuthenticatedSessionController::class, 'store'])->name('front.login.store');
    Route::get('registro', fn () => view('pages::auth.register'))->name('register');
    Route::post('registro', [RegisteredUserController::class, 'store'])->name('front.register.store');
    Route::get('recuperar-clave', fn () => view('pages::auth.forgot-password'))->name('password.request');
    Route::post('recuperar-clave', [PasswordResetLinkController::class, 'store'])->name('front.password.email');
    Route::get('restablecer-clave/{token}', fn () => view('pages::auth.reset-password'))->name('password.reset');
    Route::post('restablecer-clave', [NewPasswordController::class, 'store'])->name('front.password.update');
    Route::get('verificacion-en-dos-pasos', fn () => view('pages::auth.two-factor-challenge'))->name('two-factor.login');
    Route::post('verificacion-en-dos-pasos', [TwoFactorAuthenticatedSessionController::class, 'store'])->name('front.two-factor.login.store');

    Route::redirect('login', 'ingresar', 301)->name('login.legacy');
    Route::redirect('register', 'registro', 301)->name('register.legacy');
    Route::redirect('forgot-password', 'recuperar-clave', 301)->name('password.request.legacy');
});

Route::middleware('auth')->group(function () {
    Route::post('salir', [AuthenticatedSessionController::class, 'destroy'])->name('front.logout');
    Route::post('salir-de-usuario', [ImpersonationController::class, 'stop'])->name('impersonation.stop');
    Route::get('confirmar-clave', fn () => view('pages::auth.confirm-password'))->name('password.confirm');
    Route::post('confirmar-clave', [ConfirmablePasswordController::class, 'store'])->name('front.password.confirm.store');
    Route::get('verificar-correo', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::post('verificar-correo/notificacion', [EmailVerificationNotificationController::class, 'store'])->name('front.verification.send');
    Route::get('verificar-correo/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware('signed')->name('verification.verify');

    Route::redirect('user/confirm-password', 'confirmar-clave', 301)->name('password.confirm.legacy');
    Route::redirect('email/verify', 'verificar-correo', 301)->name('verification.notice.legacy');
});

// Plaza y vitrina pública (1.3, 1.5 del TODO) — sin registro obligatorio.
Route::get('municipios', [PlazaController::class, 'municipios'])->name('municipios');
Route::get('categorias', [PlazaController::class, 'categorias'])->name('categorias');
Route::get('categorias/{categoria:slug}', [PlazaController::class, 'categoriaPublica'])->name('categorias.show');
Route::get('buscar/{municipio?}/{categoria?}', [PlazaController::class, 'legacyBuscarRedirect'])->name('buscar.legacy');
Route::get('plaza/{municipio:slug}/categorias/{categoria:slug}', [PlazaController::class, 'legacyCategoryRedirect'])
    ->name('plaza.category.legacy')
    ->withoutScopedBindings();
Route::get('plaza/{municipio?}/{categoria?}', [PlazaController::class, 'buscar'])->name('buscar');

Route::get('m/{business:slug}', [VitrinaController::class, 'show'])->name('vitrinas.show');
Route::get('m/{business:slug}/productos/{product:slug}', [VitrinaController::class, 'product'])->name('vitrinas.product');
Route::get('m/{business:slug}/qr', [VitrinaController::class, 'qr'])->name('vitrinas.qr');
Route::get('m/{business:slug}/productos/{product:slug}/qr', [VitrinaController::class, 'qrProduct'])->name('vitrinas.qr.product');
Route::get('m/{business:slug}/whatsapp', [VitrinaController::class, 'whatsapp'])->name('vitrinas.whatsapp');
Route::get('m/{business:slug}/productos/{product:slug}/whatsapp', [VitrinaController::class, 'whatsappProduct'])->name('vitrinas.whatsapp.product');
Route::post('m/{business:slug}/compartir', [VitrinaController::class, 'compartir'])
    ->middleware('throttle:60,1')
    ->name('vitrinas.compartir');
Route::post('m/{business:slug}/productos/{product:slug}/compartir', [VitrinaController::class, 'compartirProduct'])
    ->middleware('throttle:60,1')
    ->name('vitrinas.compartir.product');

Route::get('m/{business:slug}/reportar', [ReportController::class, 'createBusiness'])->name('reportes.crear.negocio');
Route::post('m/{business:slug}/reportar', [ReportController::class, 'storeBusiness'])
    ->middleware('throttle:10,1')
    ->name('reportes.guardar.negocio');
Route::get('m/{business:slug}/productos/{product:slug}/reportar', [ReportController::class, 'createProduct'])->name('reportes.crear.producto');
Route::post('m/{business:slug}/productos/{product:slug}/reportar', [ReportController::class, 'storeProduct'])
    ->middleware('throttle:10,1')
    ->name('reportes.guardar.producto');

Route::get('m/{business:slug}/recomendaciones/{recommendation}/reportar', [ReportController::class, 'createRecommendation'])->name('reportes.crear.recomendacion');
Route::post('m/{business:slug}/recomendaciones/{recommendation}/reportar', [ReportController::class, 'storeRecommendation'])
    ->middleware('throttle:10,1')
    ->name('reportes.guardar.recomendacion');

Route::post('clientes/municipio', [ClientesController::class, 'setMunicipio'])->name('clientes.municipio');

// "Pídelo en Merkamigo" (Fase 2 del TODO) — explorar es público.
Route::get('pidelo', [NeedsController::class, 'index'])->name('pidelo');
Route::get('pidelo/{need}', [NeedsController::class, 'show'])->whereNumber('need')->name('pidelo.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clientes/favoritos', [ClientesController::class, 'favoritos'])->name('clientes.favoritos');
    Route::get('clientes/actividad', [ClientesController::class, 'actividad'])->name('clientes.actividad');
    Route::post('clientes/actividad/{notification}/leida', [ClientesController::class, 'marcarActividadLeida'])
        ->name('clientes.actividad.leida');
    Route::livewire('clientes/pedidos', 'pages::clientes.pedidos')->name('clientes.pedidos');

    Route::livewire('pidelo/nueva', 'pages::pidelo.nueva')->name('pidelo.nueva');
    Route::get('mis-solicitudes', [NeedsController::class, 'misSolicitudes'])->name('mis-solicitudes');
    Route::livewire('mis-solicitudes/{need}', 'pages::mis-solicitudes.show')->name('mis-solicitudes.show');
    Route::get('mis-solicitudes/{need}/propuestas/{offer}/whatsapp', [NeedsController::class, 'whatsapp'])
        ->name('mis-solicitudes.whatsapp');

    Route::get('emprendedores', [EmprendedoresController::class, 'home'])->name('emprendedores.home');

    Route::livewire('emprendedores/crear-vitrina', 'pages::emprendedores.crear-vitrina')
        ->name('emprendedores.crear-vitrina');

    Route::middleware('business.team')->prefix('emprendedores/negocios/{business}')->name('emprendedores.negocios.')->group(function () {
        Route::livewire('vitrina', 'pages::emprendedores.negocios.vitrina')->name('vitrina');
        Route::livewire('mi-stand', 'pages::emprendedores.negocios.mi-stand')->name('mi-stand');
        Route::livewire('productos', 'pages::emprendedores.negocios.productos')->name('productos');
        Route::livewire('colaboradores', 'pages::emprendedores.negocios.colaboradores')->name('colaboradores');
        Route::livewire('metricas', 'pages::emprendedores.negocios.metricas')->name('metricas');
        Route::get('metricas/exportar', [MetricsExportController::class, 'export'])->name('metricas.exportar');
        Route::livewire('copiloto', 'pages::emprendedores.negocios.copiloto')->name('copiloto');
        Route::livewire('oportunidades', 'pages::emprendedores.negocios.oportunidades')->name('oportunidades');
        Route::livewire('verificacion', 'pages::emprendedores.negocios.verificacion')->name('verificacion');
        Route::livewire('plan', 'pages::emprendedores.negocios.plan')->name('plan');
        Route::livewire('chatbot', 'pages::emprendedores.negocios.chatbot')->name('chatbot');
        Route::get('plan/checkout/{plan}', [CheckoutController::class, 'createForPlan'])->name('plan.checkout');
        Route::get('plan/tarjeta/tokens-aceptacion', [PaymentSourceController::class, 'acceptanceTokens'])->name('plan.tarjeta.tokens-aceptacion');
        Route::post('plan/tarjeta', [PaymentSourceController::class, 'store'])->name('plan.tarjeta.store');
        Route::get('plan/tarjeta/estado', [PaymentSourceController::class, 'status'])->name('plan.tarjeta.estado');
        Route::delete('plan/tarjeta', [PaymentSourceController::class, 'destroy'])->name('plan.tarjeta.destroy');
        Route::livewire('impulsar', 'pages::emprendedores.negocios.impulsar')->name('impulsar');
        Route::get('impulsar/checkout/{billingProduct}', [CheckoutController::class, 'createForBillingProduct'])->name('impulsar.checkout');
        Route::get('verificacion/documento', [BusinessVerificationDocumentController::class, 'show'])->name('verificacion.documento');
        Route::get('vista-previa', [EmprendedoresController::class, 'vistaPrevia'])->name('vista-previa');
        Route::get('compartir', [EmprendedoresController::class, 'compartir'])->name('compartir');
    });
});

require __DIR__.'/settings.php';
