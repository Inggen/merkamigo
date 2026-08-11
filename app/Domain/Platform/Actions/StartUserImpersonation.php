<?php

namespace App\Domain\Platform\Actions;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Session\Store;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StartUserImpersonation
{
    public const SESSION_KEY = 'impersonation';

    public function __construct(
        private readonly AuthManager $auth,
        private readonly Store $session,
    ) {}

    public function handle(User $impersonator, User $target): void
    {
        if (! $impersonator->hasAnyPlatformRole(['superadmin'])) {
            throw new AccessDeniedHttpException;
        }

        if ($impersonator->is($target)) {
            throw new AccessDeniedHttpException('No puedes entrar como tu propia cuenta.');
        }

        $this->session->put(self::SESSION_KEY, [
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'started_at' => now()->toIso8601String(),
        ]);

        $this->auth->guard('web')->login($target);
        $this->session->migrate(true);

        app(RecordAuditLog::class)->handle($impersonator, 'auth.impersonation_started', $target, [
            'target_user_id' => $target->id,
            'target_user_email' => $target->email,
        ]);
    }
}
