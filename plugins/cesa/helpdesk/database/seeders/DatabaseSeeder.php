<?php

namespace Cesa\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PrioritySeeder::class,
            TicketStatusSeeder::class,
        ]);
    }
}
