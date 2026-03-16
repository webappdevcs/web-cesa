<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\TicketStatusResource\Pages;

use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\TicketStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListTicketStatuses extends ManageRecords
{
    protected static string $resource = TicketStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
