<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\EmploymentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Country;

class EmploymentTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmploymentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->name,
            'country_id' => Country::factory(),
            'creator_id' => User::query()->value('id') ?? User::factory(),
            'code'       => fake()->word,
            'sequence'   => fake()->numberBetween(1, 100),
            'sort'       => fake()->numberBetween(1, 100),
        ];
    }
}
