<?php

namespace Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource\Pages;

use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomAssetAttribute extends EditRecord
{
    protected static string $resource = CustomAssetAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
