<?php

namespace Cesa\Shelf\Filament\Resources\AssetTransferResource\Pages;

use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetTransfers extends ListRecords
{
    protected static string $resource = AssetTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
