<?php

namespace Cesa\Shelf\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

abstract class ShelfResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('shelf');
    }

    public static function getNavigationGroup(): ?string
    {
        $translationKey = static::getResourceTranslationKey('navigation.group');
        $group = __($translationKey);

        if ($group === $translationKey) {
            return __('admin.navigation.shelf');
        }

        return $group;
    }

    public static function getNavigationLabel(): string
    {
        return __(static::getResourceTranslationKey('navigation.title'));
    }

    public static function getModel(): string
    {
        if (filled(static::$model)) {
            return static::$model;
        }

        return (string) str(class_basename(static::class))
            ->beforeLast('Resource')
            ->prepend('Cesa\\Shelf\\Models\\');
    }

    public static function getModelLabel(): string
    {
        return __(static::getResourceTranslationKey('singular'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::getResourceTranslationKey('plural'));
    }

    protected static function getResourceTranslationKey(string $suffix): string
    {
        $resourceKey = str(class_basename(static::class))
            ->beforeLast('Resource')
            ->kebab()
            ->toString();

        return "shelf::filament.resources.{$resourceKey}.{$suffix}";
    }
}
