<?php

namespace App\Providers;

use App\Domain\Businesses\Events\BusinessRestored;
use App\Domain\Businesses\Events\BusinessSuspended;
use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Policies\BusinessPolicy;
use App\Domain\Immersive\Contracts\GeneratesVoxelObjectDefinition;
use App\Domain\Immersive\Observers\BusinessStandObserver;
use App\Domain\Immersive\Support\OpenAiVoxelObjectGenerator;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Needs\Policies\NeedPolicy;
use App\Domain\Needs\Policies\OfferPolicy;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Events\ProductCreated;
use App\Domain\Storefronts\Events\ProductUpdated;
use App\Domain\Storefronts\Jobs\DeleteProductFromGoogleMerchant;
use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Policies\OrderConfirmationPolicy;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use App\Support\Ai\Contracts\GeneratesImages;
use App\Support\Ai\Contracts\TranscribesAudio;
use App\Support\Ai\NullAudioTranscriber;
use App\Support\Ai\OpenAiImageGenerator;
use App\Support\Ai\OpenAiTextGenerator;
use App\Support\Geo\Contracts\GeocodesAddresses;
use App\Support\Geo\ManualGeocoder;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeneratesAssistedText::class, OpenAiTextGenerator::class);
        $this->app->bind(GeneratesImages::class, OpenAiImageGenerator::class);
        $this->app->bind(TranscribesAudio::class, NullAudioTranscriber::class);
        $this->app->bind(GeneratesVoxelObjectDefinition::class, OpenAiVoxelObjectGenerator::class);

        // Contrato de geocodificación (5.4 del TODO): sin proveedor real
        // elegido todavía, `ManualGeocoder` mantiene el comportamiento
        // actual (coordenadas manuales). Cambiar de proveedor más
        // adelante es solo cambiar este binding.
        $this->app->bind(GeocodesAddresses::class, ManualGeocoder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureAuditing();
        $this->configureRateLimiting();
        $this->configureImmersiveStandSync();
        $this->configureRegistrationTracking();
        $this->configureGoogleMerchantSync();
    }

    /**
     * IMM-022/IMM-023 del TODO inmersivo: el negocio no sabe nada de la
     * experiencia inmersiva — el observer vive en el dominio Immersive y
     * se registra aquí, no dentro de `Business`.
     */
    protected function configureImmersiveStandSync(): void
    {
        Business::observe(BusinessStandObserver::class);
    }

    protected function configureAuthorization(): void
    {
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Need::class, NeedPolicy::class);
        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(OrderConfirmation::class, OrderConfirmationPolicy::class);
    }

    protected function configureAuditing(): void
    {
        Event::listen(function (Login $event) {
            app(RecordAuditLog::class)->handle($event->user, 'auth.login');
        });
    }

    /**
     * Marca en sesión que el visitante acaba de registrarse, para que
     * `partials/head.blade.php` dispare el evento de conversión
     * `CompleteRegistration` del Meta Pixel en la página a la que aterrice
     * (el registro redirige a `/dashboard`, que a su vez puede redirigir de
     * nuevo según la experiencia del usuario) sin acoplar el pixel a cada
     * vista de destino.
     */
    protected function configureRegistrationTracking(): void
    {
        Event::listen(function (Registered $event) {
            session()->put('just_registered', true);
        });
    }

    /**
     * Primer enganche de sincronización con Google Merchant Center:
     * `SyncProductToGoogleMerchant` decide por su cuenta si publica,
     * actualiza o retira el producto (según elegibilidad), así que
     * `ProductCreated`/`ProductUpdated` siempre disparan el mismo Job —
     * no hay que distinguir el caso aquí. Al suspender un negocio
     * completo sí se fuerza el retiro directo de cada producto, sin
     * esperar a que cada uno se re-evalúe individualmente.
     */
    protected function configureGoogleMerchantSync(): void
    {
        Event::listen(function (ProductCreated $event) {
            SyncProductToGoogleMerchant::dispatch($event->productId)->afterCommit();
        });

        Event::listen(function (ProductUpdated $event) {
            SyncProductToGoogleMerchant::dispatch($event->productId)->afterCommit();
        });

        Event::listen(function (BusinessSuspended $event) {
            Business::find($event->businessId)
                ?->products()
                ->where('type', 'producto')
                ->pluck('id')
                ->each(fn (int $productId) => DeleteProductFromGoogleMerchant::dispatch($productId)->afterCommit());
        });

        Event::listen(function (BusinessRestored $event) {
            Business::find($event->businessId)
                ?->products()
                ->where('type', 'producto')
                ->pluck('id')
                ->each(fn (int $productId) => SyncProductToGoogleMerchant::dispatch($productId)->afterCommit());
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
