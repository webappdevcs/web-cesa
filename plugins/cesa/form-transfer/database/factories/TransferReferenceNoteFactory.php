<?php

namespace Cesa\FormTransfer\Database\Factories;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferReferenceNoteFactory extends Factory
{
    protected $model = TransferReferenceNote::class;

    public function definition(): array
    {
        return [
            'form_transfer_id' => FormTransfer::factory(),
            'label'            => $this->faker->words(3, true),
            'description'      => $this->faker->optional()->sentence(),
            'is_active'        => true,
        ];
    }
}
