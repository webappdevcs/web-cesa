<?php

namespace Cesa\Shelf\Filament\Resources\VehicleChecksheetResource\Pages;

use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleChecksheet extends EditRecord
{
    protected static string $resource = VehicleChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
