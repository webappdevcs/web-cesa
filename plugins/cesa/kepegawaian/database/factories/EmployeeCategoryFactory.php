<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\EmployeeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;

class EmployeeCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmployeeCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'    => fake()->name,
            'color'   => fake()->hexColor,
            'user_id' => User::query()->value('id') ?? User::factory(),
        ];
    }
}
