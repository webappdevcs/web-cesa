<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeSyncConflictFactory extends Factory
{
    protected $model = EmployeeSyncConflict::class;

    public function definition(): array
    {
        return [
            'source_record_id' => EmployeeSourceRecord::factory(),
            'type'             => 'invalid_record',
            'status'           => 'open',
            'details'          => ['reason' => 'Factory conflict'],
            'employee_id'      => null,
        ];
    }
}
