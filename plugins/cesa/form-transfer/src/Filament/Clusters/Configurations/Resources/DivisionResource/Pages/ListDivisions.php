<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\DivisionResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\DivisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDivisions extends ListRecords
{
    protected static string $resource = DivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}
