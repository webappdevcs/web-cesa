<?php

namespace Cesa\Shelf\Filament\Resources\AssetRequestResource\Pages;

use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetRequests extends ManageRecords
{
    protected static string $resource = AssetRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AssetRequestResource::getCreateAction(),
        ];
    }
}
