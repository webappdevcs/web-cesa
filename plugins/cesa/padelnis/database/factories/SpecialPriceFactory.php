<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\SpecialPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialPrice>
 */
class SpecialPriceFactory extends Factory
{
    protected $model = SpecialPrice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_item_id'  => CatalogItem::factory(),
            'component'        => null,
            'court_id'         => null,
            'court_type'       => null,
            'coach_id'         => null,
            'time_slot'        => null,
            'day_type'         => null,
            'name'             => $this->faker->words(2, true),
            'amount'           => $this->faker->numberBetween(75000, 300000),
            'calculation_type' => CatalogItem::CALCULATION_FIXED,
            'percentage'       => null,
            'basis'            => CatalogItem::BASIS_QUOTED_AMOUNT,
            'priority'         => 0,
            'starts_at'        => null,
            'ends_at'          => null,
            'is_active'        => true,
        ];
    }
}
