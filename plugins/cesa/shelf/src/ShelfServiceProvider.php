<?php

namespace Cesa\Shelf;

use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ShelfServiceProvider extends PackageServiceProvider
{
    public static string $name = 'shelf';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command): void {})
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ShelfPlugin::make());
        });
    }
}
