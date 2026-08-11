<?php

namespace App\Domain\Platform\Actions;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Session\Store;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StopUserImpersonation
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Store $session,
    ) {}

    public function handle(User $currentUser): User
    {
        $impersonation = $this->session->get(StartUserImpersonation::SESSION_KEY);
        $impersonatorId = data_get($impersonation, 'impersonator_id');

        if (! $impersonatorId) {
            throw new AccessDeniedHttpException;
        }

        $impersonator = User::findOrFail($impersonatorId);

        if (! $impersonator->hasAnyPlatformRole(['superadmin'])) {
            throw new AccessDeniedHttpException;
        }

        $this->auth->guard('web')->login($impersonator);
        $this->session->forget(StartUserImpersonation::SESSION_KEY);
        $this->session->migrate(true);

        app(RecordAuditLog::class)->handle($impersonator, 'auth.impersonation_stopped', $currentUser, [
            'impersonated_user_id' => $currentUser->id,
            'impersonated_user_email' => $currentUser->email,
        ]);

        return $impersonator;
    }
}
