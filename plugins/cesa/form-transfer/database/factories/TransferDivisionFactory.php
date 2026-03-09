<?php

namespace Cesa\FormTransfer\Database\Factories;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferDivision;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferDivisionFactory extends Factory
{
    protected $model = TransferDivision::class;

    public function definition(): array
    {
        return [
            'form_transfer_id' => FormTransfer::factory(),
            'name'             => $this->faker->words(2, true),
            'code'             => $this->faker->unique()->lexify('DIV-????'),
            'description'      => $this->faker->optional()->sentence(),
            'is_active'        => true,
        ];
    }
}
