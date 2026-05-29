<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionType>
 */
class TransactionTypeFactory extends Factory
{
    protected $model = TransactionType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'                  => $this->faker->unique()->slug(2),
            'name'                  => $this->faker->words(2, true),
            'requires_coach'        => false,
            'requires_catalog_item' => false,
            'is_active'             => true,
            'sort'                  => $this->faker->numberBetween(1, 100),
        ];
    }
}
