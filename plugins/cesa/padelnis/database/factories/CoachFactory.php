<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Models\Coach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coach>
 */
class CoachFactory extends Factory
{
    protected $model = Coach::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->name(),
            'phone'     => $this->faker->phoneNumber(),
            'is_active' => true,
            'sort'      => $this->faker->numberBetween(1, 100),
        ];
    }
}
