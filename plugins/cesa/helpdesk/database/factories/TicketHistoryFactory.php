<?php

namespace Cesa\Helpdesk\Database\Factories;

use App\Models\User;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketHistory;
use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketHistoryFactory extends Factory
{
    protected $model = TicketHistory::class;

    public function definition(): array
    {
        return [
            'ticket_id'        => Ticket::factory(),
            'ticket_status_id' => TicketStatus::factory(),
            'user_id'          => User::factory(),
        ];
    }
}
