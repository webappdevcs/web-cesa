<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\UnitResource\Pages;

use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
