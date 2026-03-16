<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\DepartureReason;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartureReasonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DepartureReason::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sequence'    => fake()->randomNumber(),
            'reason_code' => fake()->word,
            'name'        => fake()->word,
        ];
    }
}
