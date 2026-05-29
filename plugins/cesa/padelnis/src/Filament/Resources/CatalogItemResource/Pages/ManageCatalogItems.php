<?php

namespace Cesa\Padelnis\Filament\Resources\CatalogItemResource\Pages;

use Cesa\Padelnis\Filament\Resources\CatalogItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCatalogItems extends ManageRecords
{
    protected static string $resource = CatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
