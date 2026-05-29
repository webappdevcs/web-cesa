<?php

namespace Cesa\Padelnis\Database\Factories;

use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'transaction_type' => TransactionType::Regular,
            'customer_name'    => $this->faker->name(),
            'reservation_date' => $this->faker->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'court'            => $this->faker->randomElement(array_values(config('padelnis.courts', ['Padel Court VIP Blue 1']))),
            'reservation_time' => $this->faker->randomElement(array_values(config('padelnis.slots', ['10:00 - 11:00']))),
            'expected_amount'  => null,
            'transfer_amount'  => $this->faker->numberBetween(100000, 500000),
            'transfer_date'    => $this->faker->dateTimeBetween('-7 days', 'today')->format('Y-m-d'),
            'notes'            => null,
        ];
    }
}
