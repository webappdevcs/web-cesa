<?php

namespace Cesa\Shelf\Support;

use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\VehicleChecksheet;
use InvalidArgumentException;

class ShelfManagedFileRegistry
{
    /**
     * @return array<string, class-string>
     */
    public static function modelMap(): array
    {
        return [
            'asset'                    => Asset::class,
            'asset-request'            => AssetRequest::class,
            'asset-transfer'           => AssetTransfer::class,
            'company-document-setting' => CompanyDocumentSetting::class,
            'task'                     => Task::class,
            'vehicle-checksheet'       => VehicleChecksheet::class,
        ];
    }

    public static function modelForType(string $type): ?string
    {
        return self::modelMap()[$type] ?? null;
    }

    public static function typeForModel(string $modelClass): string
    {
        $type = array_search($modelClass, self::modelMap(), true);

        if (! is_string($type)) {
            throw new InvalidArgumentException("Managed file type is not registered for model [{$modelClass}].");
        }

        return $type;
    }
}
