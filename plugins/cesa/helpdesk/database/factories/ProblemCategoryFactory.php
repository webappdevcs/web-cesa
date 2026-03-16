<?php

namespace Cesa\Helpdesk\Database\Factories;

use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProblemCategoryFactory extends Factory
{
    protected $model = ProblemCategory::class;

    public function definition(): array
    {
        return [
            'unit_id'                => Unit::factory(),
            'name'                   => $this->faker->unique()->words(2, true),
            'default_responsible_id' => null,
        ];
    }
}
