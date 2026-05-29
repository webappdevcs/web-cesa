<?php

namespace Cesa\Padelnis\Filament\Resources\CoachResource\Pages;

use Cesa\Padelnis\Filament\Resources\CoachResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCoaches extends ManageRecords
{
    protected static string $resource = CoachResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}
