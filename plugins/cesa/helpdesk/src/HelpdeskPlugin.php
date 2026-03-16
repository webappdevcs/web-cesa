<?php

namespace Cesa\Helpdesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\PluginManager\Package;

class HelpdeskPlugin implements Plugin
{
    public function getId(): string
    {
        return 'helpdesk';
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

        $panel
            ->when($panel->getId() === 'admin', function (Panel $panel): void {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Cesa\\Helpdesk\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Cesa\\Helpdesk\\Filament\\Pages')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Cesa\\Helpdesk\\Filament\\Widgets');
            });
    }

    public function boot(Panel $panel): void {}

    protected function getPluginBasePath(?string $path = null): string
    {
        $reflector = new ReflectionClass(static::class);

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
