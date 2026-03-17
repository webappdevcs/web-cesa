<?php

namespace Cesa\Shelf\Filament\Resources\CategoryResource\Pages;

use Cesa\Shelf\Filament\Resources\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
