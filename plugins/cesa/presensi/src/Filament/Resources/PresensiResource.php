<?php

namespace Cesa\Presensi\Filament\Resources;

use BackedEnum;
use Cesa\Presensi\Traits\HasPresensiResourceAccess;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;

abstract class PresensiResource extends Resource
{
    use HasPresensiResourceAccess;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('presensi');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.presensi');
    }
}
