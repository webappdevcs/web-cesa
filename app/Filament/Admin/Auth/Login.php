<?php

namespace App\Filament\Admin\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.admin.components.layouts.auth';

    protected string $view = 'filament.admin.auth.login';

    public function getTitle(): string|Htmlable
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getTitle();
        }

        return 'Masuk';
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Sign in';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return null;
    }
}
