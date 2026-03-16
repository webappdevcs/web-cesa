<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;

use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
