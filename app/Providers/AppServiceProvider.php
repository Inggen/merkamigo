<?php

namespace App\Providers;

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
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Policies\OrderConfirmationPolicy;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use App\Support\Ai\Contracts\TranscribesAudio;
use App\Support\Ai\NullAudioTranscriber;
use App\Support\Ai\OpenAiTextGenerator;
use App\Support\Geo\Contracts\GeocodesAddresses;
use App\Support\Geo\ManualGeocoder;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
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
