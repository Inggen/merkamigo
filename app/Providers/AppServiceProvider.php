<?php

namespace App\Providers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Policies\BusinessPolicy;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Policies\NeedPolicy;
use App\Domain\Platform\Actions\RecordAuditLog;
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
        //
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
    }

    protected function configureAuthorization(): void
    {
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Need::class, NeedPolicy::class);
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
