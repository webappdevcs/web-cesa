<?php

namespace Cesa\Helpdesk\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;

abstract class HelpdeskResource extends Resource
{
    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    protected static bool $navigationConfigured = false;

    public static function shouldRegisterNavigation(): bool
    {
        static::bootNavigation();

        return Package::isPluginInstalled('helpdesk');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.helpdesk');
    }

    public static function getNavigationIcon(): ?string
    {
        static::bootNavigation();

        return static::$navigationIcon;
    }

    protected static function bootNavigation(): void
    {
        if (static::$navigationConfigured) {
            return;
        }

        if (static::$navigationIcon === null) {
            static::$navigationIcon = 'icon-helpdesk';
        }

        if (static::$navigationSort === null) {
            static::$navigationSort = 1;
        }

        static::$navigationConfigured = true;
    }
}
