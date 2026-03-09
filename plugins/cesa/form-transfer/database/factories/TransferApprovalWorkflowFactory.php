<?php

namespace Cesa\FormTransfer\Database\Factories;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferApprovalWorkflowFactory extends Factory
{
    protected $model = TransferApprovalWorkflow::class;

    public function definition(): array
    {
        return [
            'form_transfer_id' => FormTransfer::factory(),
            'name'             => $this->faker->words(3, true),
            'code'             => $this->faker->unique()->lexify('WORKFLOW-????'),
            'description'      => $this->faker->optional()->sentence(),
            'steps'            => $this->buildSteps(),
            'is_active'        => true,
        ];
    }

    protected function buildSteps(): array
    {
        $steps = $this->faker->numberBetween(1, 3);

        return collect(range(1, $steps))
            ->map(function (int $index) {
                return [
                    'label'         => $this->faker->jobTitle(),
                    'default_name'  => $this->faker->name(),
                    'default_email' => $this->faker->companyEmail(),
                    'default_phone' => $this->faker->optional()->phoneNumber(),
                    'default_title' => $this->faker->jobTitle(),
                    'is_mandatory'  => $this->faker->boolean(80),
                    'sort_order'    => $index,
                ];
            })
            ->all();
    }
}
