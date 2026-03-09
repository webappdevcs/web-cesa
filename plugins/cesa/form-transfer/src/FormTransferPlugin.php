<?php

namespace Cesa\FormTransfer;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\PluginManager\Package;

class FormTransferPlugin implements Plugin
{
    public function getId(): string
    {
        return 'form-transfer';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel->when($panel->getId() === 'admin', function (Panel $panel): void {
            $panel
                ->discoverResources(
                    in: $this->getPluginBasePath('/Filament/Resources'),
                    for: 'Cesa\\FormTransfer\\Filament\\Resources'
                )
                ->discoverClusters(
                    in: $this->getPluginBasePath('/Filament/Clusters'),
                    for: 'Cesa\\FormTransfer\\Filament\\Clusters'
                );
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function getPluginBasePath(?string $path = null): string
    {
        $reflector = new ReflectionClass(static::class);

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
