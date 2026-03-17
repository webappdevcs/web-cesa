<?php

namespace Cesa\Shelf\Support;

class ShelfStorage
{
    public static function disk(): string
    {
        $configuredDisk = config('filament.default_filesystem_disk')
            ?: config('filesystems.default')
            ?: 'local';

        if (! is_string($configuredDisk) || ! config()->has("filesystems.disks.{$configuredDisk}")) {
            return 'local';
        }

        return $configuredDisk;
    }
}
