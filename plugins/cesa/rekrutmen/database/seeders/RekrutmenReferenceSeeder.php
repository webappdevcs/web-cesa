<?php

namespace Cesa\Rekrutmen\Database\Seeders;

use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Illuminate\Database\Seeder;

class RekrutmenReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = RekrutmenPipeline::query()->firstOrCreate(
            ['name' => 'Default Recruitment Pipeline'],
            ['description' => 'Pipeline standar proses rekrutmen.'],
        );

        $stages = [
            1 => 'Screening CV',
            2 => 'Interview HR',
            3 => 'Interview User',
            4 => 'Offering',
            5 => 'Hired',
        ];

        foreach ($stages as $order => $name) {
            $pipeline->stages()->updateOrCreate(
                ['name' => $name],
                ['order_column' => $order],
            );
        }
    }
}
