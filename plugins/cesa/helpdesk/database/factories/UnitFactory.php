<?php

namespace Cesa\Helpdesk\Database\Factories;

use Cesa\Helpdesk\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name'        => strtoupper($this->faker->unique()->lexify('UNIT ???')),
            'description' => $this->faker->sentence(),
        ];
    }
}
