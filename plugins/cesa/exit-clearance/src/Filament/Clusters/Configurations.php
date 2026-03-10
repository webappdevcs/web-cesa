<?php

namespace Cesa\ExitClearance\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

class Configurations extends Cluster
{
    public static function getSlug(?Panel $panel = null): string
    {
        return 'exit-clearance/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('exit-clearance::app.config.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.exit-clearance');
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
