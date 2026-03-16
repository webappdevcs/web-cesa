<?php

namespace Cesa\Kepegawaian\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

class Configurations extends Cluster
{
    protected static ?int $navigationSort = 4;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('kepegawaian::filament/clusters/configurations.navigation.group');
    }
}
