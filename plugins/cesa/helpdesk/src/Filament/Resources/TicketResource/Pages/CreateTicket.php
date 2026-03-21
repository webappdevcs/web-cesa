<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;

use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Cesa\Helpdesk\Models\TicketStatus;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['responsible_id']);

        $data['owner_id'] = auth()->id();
        $data['ticket_status_id'] = $data['ticket_status_id'] ?? TicketStatus::OPEN;

        return $data;
    }
}
