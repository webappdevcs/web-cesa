<?php

namespace Cesa\FormTransfer\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

class Configurations extends Cluster
{
    public static function getSlug(?Panel $panel = null): string
    {
        return 'form-transfer/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::app.config.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.form-transfer');
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
