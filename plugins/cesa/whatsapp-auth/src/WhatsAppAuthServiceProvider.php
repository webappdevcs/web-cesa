<?php

namespace Cesa\WhatsAppAuth;

use Cesa\WhatsAppAuth\Contracts\WhatsAppGateway;
use Cesa\WhatsAppAuth\Services\Gateways\FonnteGateway;
use Cesa\WhatsAppAuth\Services\Gateways\LogGateway;
use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class WhatsAppAuthServiceProvider extends PackageServiceProvider
{
    public static string $name = 'whatsapp-auth';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                '2026_06_08_000000_whatsapp_auth_create_whatsapp_otp_codes_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        $this->app->bind(WhatsAppGateway::class, function (): WhatsAppGateway {
            return match (strtolower((string) config('whatsapp-auth.gateway', 'fonnte'))) {
                'log'   => new LogGateway,
                default => new FonnteGateway(
                    endpoint: (string) config('whatsapp-auth.fonnte.endpoint'),
                    token: config('whatsapp-auth.fonnte.token'),
                    countryCode: (string) config('whatsapp-auth.fonnte.country_code', '62'),
                    timeout: (int) config('whatsapp-auth.fonnte.timeout', 10),
                ),
            };
        });

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(WhatsAppAuthPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        //
    }
}
