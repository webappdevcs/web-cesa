<?php

namespace Cesa\Shelf\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;
use Webkul\PluginManager\Package;

class Configurations extends Cluster
{
    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('shelf');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'shelf/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('shelf::app.config.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.shelf');
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
