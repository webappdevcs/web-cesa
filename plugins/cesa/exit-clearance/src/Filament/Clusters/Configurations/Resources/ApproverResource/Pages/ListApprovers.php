<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource\Pages;

use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListApprovers extends ManageRecords
{
    protected static string $resource = ApproverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
