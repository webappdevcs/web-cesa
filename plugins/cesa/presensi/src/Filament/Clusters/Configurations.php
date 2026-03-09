<?php

namespace Cesa\Presensi\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;
use Webkul\PluginManager\Package;

class Configurations extends Cluster
{
    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('presensi');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'presensi/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.config.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.presensi');
    }

    public static function getNavigationIcon(): ?string
    {
        return null;
    }

    public static function getNavigationSort(): ?int
    {
        return 1000;
    }
}
