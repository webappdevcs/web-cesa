<?php

namespace Cesa\ExitClearance\Database\Factories;

use Cesa\ExitClearance\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code'        => fake()->unique()->bothify('DEPT-####'),
            'name'        => fake()->word(),
            'description' => fake()->sentence(),
            'created_by'  => null,
        ];
    }
}
