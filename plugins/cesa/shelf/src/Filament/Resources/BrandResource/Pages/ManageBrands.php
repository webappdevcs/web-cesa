<?php

namespace Cesa\Shelf\Filament\Resources\BrandResource\Pages;

use Cesa\Shelf\Filament\Resources\BrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBrands extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
