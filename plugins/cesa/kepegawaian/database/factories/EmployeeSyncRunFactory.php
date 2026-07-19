<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeSyncRunFactory extends Factory
{
    protected $model = EmployeeSyncRun::class;

    public function definition(): array
    {
        return [
            'source_system'   => 'talenta',
            'source_instance' => 'testing',
            'mode'            => 'dry_run',
            'status'          => 'completed',
            'file_name'       => 'employees.json',
            'file_checksum'   => hash('sha256', fake()->uuid()),
            'channel'         => 'testing',
            'started_at'      => now(),
            'completed_at'    => now(),
        ];
    }
}
