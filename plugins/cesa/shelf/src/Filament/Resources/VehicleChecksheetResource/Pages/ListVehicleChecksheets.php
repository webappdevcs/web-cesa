<?php

namespace Cesa\Shelf\Filament\Resources\VehicleChecksheetResource\Pages;

use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleChecksheets extends ListRecords
{
    protected static string $resource = VehicleChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
