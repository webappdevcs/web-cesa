<?php

namespace Cesa\Shelf\Filament\Resources\VehicleChecksheetResource\Pages;

use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleChecksheet extends CreateRecord
{
    protected static string $resource = VehicleChecksheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_number'] = VehicleChecksheetResource::generateReferenceNumber(true);

        return $data;
    }
}
