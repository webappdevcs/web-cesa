<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\PriorityResource\Pages;

use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\PriorityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListPriorities extends ManageRecords
{
    protected static string $resource = PriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
