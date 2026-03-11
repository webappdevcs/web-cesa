<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages;

use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListDepartments extends ManageRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
