<?php

namespace Cesa\WhatsAppAuth;

use Cesa\WhatsAppAuth\Filament\Pages\Auth\WhatsAppLogin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Webkul\PluginManager\Package;

class WhatsAppAuthPlugin implements Plugin
{
    public const LOGIN_ROUTE = 'whatsapp-auth.login';

    public function getId(): string
    {
        return 'whatsapp-auth';
    }

    public function register(Panel $panel): void
    {
        if (! static::isEnabled()) {
            return;
        }

        if ($panel->getId() !== static::panelId()) {
            return;
        }

        Livewire::component(WhatsAppLogin::class, WhatsAppLogin::class);

        $panel->routes(function (Panel $panel): void {
            Route::get('whatsapp-login', WhatsAppLogin::class)
                ->name(static::LOGIN_ROUTE);
        });

        $panel->renderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => view('whatsapp-auth::login-link')->render(),
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function isEnabled(): bool
    {
        return Package::isPluginInstalled('whatsapp-auth');
    }

    public static function panelId(): string
    {
        return (string) config('whatsapp-auth.panel', 'admin');
    }

    /**
     * Resolve the URL of the WhatsApp login page for the configured panel.
     */
    public static function loginUrl(): ?string
    {
        if (! static::isEnabled()) {
            return null;
        }

        $routeName = 'filament.'.static::panelId().'.'.static::LOGIN_ROUTE;

        return Route::has($routeName) ? route($routeName) : null;
    }
}
