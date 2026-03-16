<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeCategory;
use Cesa\Kepegawaian\Models\EmployeeEmployeeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeEmployeeCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmployeeEmployeeCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'category_id' => EmployeeCategory::factory(),
        ];
    }
}
