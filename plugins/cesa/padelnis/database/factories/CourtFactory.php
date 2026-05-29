<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    protected $model = Court::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => 'Court '.$this->faker->unique()->numberBetween(1, 99),
            'court_type' => 'Regular',
            'is_active'  => true,
            'sort'       => $this->faker->numberBetween(1, 100),
        ];
    }
}
