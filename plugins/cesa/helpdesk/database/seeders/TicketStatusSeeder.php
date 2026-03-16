<?php

namespace Cesa\Helpdesk\Database\Seeders;

use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => TicketStatus::OPEN, 'name' => 'Open'],
            ['id' => TicketStatus::IN_PROGRESS, 'name' => 'In Progress'],
            ['id' => TicketStatus::CANCELLED, 'name' => 'Cancelled'],
            ['id' => TicketStatus::CLOSED, 'name' => 'Closed'],
        ];

        foreach ($records as $record) {
            TicketStatus::query()->updateOrCreate(['id' => $record['id']], $record);
        }
    }
}
