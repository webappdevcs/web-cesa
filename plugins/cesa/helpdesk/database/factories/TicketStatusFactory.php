<?php

namespace Cesa\Helpdesk\Database\Factories;

use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
