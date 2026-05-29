<?php

namespace Cesa\Padelnis\Filament\Resources\CourtResource\Pages;

use Cesa\Padelnis\Filament\Resources\CourtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCourts extends ManageRecords
{
    protected static string $resource = CourtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
