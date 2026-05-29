<?php

namespace Cesa\Padelnis;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\PluginManager\Package;

class PadelnisPlugin implements Plugin
{
    public function getId(): string
    {
        return 'padelnis';
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
                    for: 'Cesa\\Padelnis\\Filament\\Resources'
                )
                ->discoverClusters(
                    in: $this->getPluginBasePath('/Filament/Clusters'),
                    for: 'Cesa\\Padelnis\\Filament\\Clusters'
                );
        });
    }

    public function boot(Panel $panel): void {}

    protected function getPluginBasePath(?string $path = null): string
    {
        $reflector = new ReflectionClass(static::class);

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
