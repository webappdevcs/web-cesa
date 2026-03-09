<?php

namespace Cesa\FormTransfer\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;

abstract class FormTransferResource extends Resource
{
    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('form-transfer');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.form-transfer');
    }

    public static function getNavigationIcon(): ?string
    {
        return null;
    }
}
