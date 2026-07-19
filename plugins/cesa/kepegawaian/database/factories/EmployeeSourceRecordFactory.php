<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeSourceRecordFactory extends Factory
{
    protected $model = EmployeeSourceRecord::class;

    public function definition(): array
    {
        $payload = [
            'id'          => fake()->unique()->uuid(),
            'id_employee' => fake()->unique()->numerify('EMP-####'),
        ];

        return [
            'sync_run_id'   => EmployeeSyncRun::factory(),
            'row_number'    => fake()->unique()->numberBetween(1, 100000),
            'external_id'   => $payload['id'],
            'employee_code' => $payload['id_employee'],
            'checksum'      => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'payload'       => $payload,
            'status'        => 'matched',
            'match_strategy'=> 'external_id',
            'employee_id'   => null,
        ];
    }
}
