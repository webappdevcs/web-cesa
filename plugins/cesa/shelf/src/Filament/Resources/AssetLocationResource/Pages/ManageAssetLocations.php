<?php

namespace Cesa\Shelf\Filament\Resources\AssetLocationResource\Pages;

use Cesa\Shelf\Filament\Resources\AssetLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetLocations extends ManageRecords
{
    protected static string $resource = AssetLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
