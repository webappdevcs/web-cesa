<?php

namespace Cesa\Shelf\Filament\Resources\VendorResource\Pages;

use Cesa\Shelf\Filament\Resources\VendorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVendors extends ManageRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
