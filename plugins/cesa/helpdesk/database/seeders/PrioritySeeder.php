<?php

namespace Cesa\Helpdesk\Database\Seeders;

use Cesa\Helpdesk\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => Priority::CRITICAL, 'name' => 'Critical/Urgent'],
            ['id' => Priority::HIGH, 'name' => 'High'],
            ['id' => Priority::MEDIUM, 'name' => 'Medium'],
            ['id' => Priority::LOW, 'name' => 'Low'],
            ['id' => Priority::ENHANCEMENT, 'name' => 'Enhancement/Feature Request'],
        ];

        foreach ($records as $record) {
            Priority::query()->updateOrCreate(['id' => $record['id']], $record);
        }
    }
}
