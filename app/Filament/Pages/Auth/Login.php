<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    protected static string $layout = 'filament-panels::components.layout.base';

    protected string $view = 'filament.pages.auth.admin-login';

    public function getTitle(): string
    {
        return 'Administracion Merkamigo';
    }

    public function getHeading(): string
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? 'Verificacion en dos pasos'
            : 'Administracion Merkamigo';
    }

    public function getSubheading(): ?string
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return 'Completa el metodo de verificacion para continuar al panel.';
        }

        return 'Gestiona negocios, solicitudes y la comunidad local.';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function hasTopbar(): bool
    {
        return false;
    }

    public function getPasswordResetUrl(): ?string
    {
        return Filament::hasPasswordReset() ? filament()->getRequestPasswordResetUrl() : null;
    }
}
