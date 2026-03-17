<?php

namespace Cesa\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'ticket_id'     => $this->ticket_id,
            'ticket_status' => $this->whenLoaded('ticketStatus', fn (): array => [
                'id'   => $this->ticketStatus?->id,
                'name' => $this->ticketStatus?->name,
            ]),
            'user'          => $this->whenLoaded('user', fn (): array => [
                'id'         => $this->user?->id,
                'name'       => $this->user?->name,
                'email'      => $this->user?->email,
                'avatar_url' => $this->user?->avatar_url,
            ]),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
