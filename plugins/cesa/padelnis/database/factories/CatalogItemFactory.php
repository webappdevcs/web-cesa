<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                 => $this->faker->words(3, true),
            'description'          => null,
            'transaction_type'     => TransactionType::Regular,
            'requires_court'       => true,
            'requires_coach'       => false,
            'pricing_mode'         => PricingMode::PerSlot,
            'time_slot'            => null,
            'price_amount'         => $this->faker->numberBetween(100000, 500000),
            'price_components'     => null,
            'duration_hours'       => 1,
            'session_count'        => null,
            'is_active'            => true,
            'allow_public_booking' => true,
            'sort'                 => $this->faker->numberBetween(1, 100),
        ];
    }

    public function coaching(): static
    {
        return $this->state(fn (): array => [
            'transaction_type' => TransactionType::Coaching,
            'requires_court'   => true,
            'requires_coach'   => true,
            'pricing_mode'     => PricingMode::PerSlot,
            'duration_hours'   => 1,
        ]);
    }

    public function academy(): static
    {
        return $this->state(fn (): array => [
            'transaction_type' => TransactionType::Academy,
            'requires_court'   => true,
            'requires_coach'   => false,
            'pricing_mode'     => PricingMode::Fixed,
            'duration_hours'   => null,
            'session_count'    => 8,
        ]);
    }

    public function coachingAcademy(): static
    {
        return $this->state(fn (): array => [
            'transaction_type' => TransactionType::Academy,
            'requires_court'   => true,
            'requires_coach'   => true,
            'pricing_mode'     => PricingMode::Fixed,
            'duration_hours'   => null,
            'session_count'    => 8,
        ]);
    }
}
