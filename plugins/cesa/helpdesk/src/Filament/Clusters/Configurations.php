<?php

namespace Cesa\Helpdesk\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;
use Webkul\PluginManager\Package;

class Configurations extends Cluster
{
    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('helpdesk') && parent::shouldRegisterNavigation();
    }

    public function mount(): void
    {
        foreach (static::getClusteredComponents() as $component) {
            if (! $component::canAccess()) {
                continue;
            }

            redirect($component::getNavigationUrl());

            return;
        }

        abort(403);
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'helpdesk/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('helpdesk::app.navigation.configurations');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.helpdesk');
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
