<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;

use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
