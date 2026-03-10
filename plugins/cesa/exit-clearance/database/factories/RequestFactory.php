<?php

namespace Cesa\ExitClearance\Database\Factories;

use Cesa\ExitClearance\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'department_id'                  => null,
            'name'                           => fake()->name(),
            'email'                          => fake()->unique()->safeEmail(),
            'phone'                          => fake()->phoneNumber(),
            'position'                       => fake()->jobTitle(),
            'placement'                      => fake()->city(),
            'join_date'                      => fake()->date(),
            'request_date'                   => fake()->date(),
            'departure_date'                 => fake()->date(),
            'reason'                         => fake()->sentence(),
            'workload_feedback'              => fake()->sentence(),
            'career_growth_feedback'         => fake()->sentence(),
            'facility_welfare_feedback'      => fake()->sentence(),
            'work_relationship_feedback'     => fake()->sentence(),
            'compensation_feedback'          => fake()->sentence(),
            'division_feedback'              => fake()->sentence(),
            'company_feedback'               => fake()->sentence(),
            'clearance_kartu_halo'           => fake()->word(),
            'clearance_employee_debt'        => fake()->word(),
            'clearance_uniform_return'       => fake()->word(),
            'clearance_vehicle_return'       => fake()->word(),
            'clearance_inventory_return'     => fake()->word(),
            'clearance_account_deactivation' => fake()->word(),
            'clearance_receivable_data'      => fake()->word(),
            'clearance_promotor_internal'    => fake()->word(),
            'clearance_nota_pending'         => fake()->word(),
            'clearance_stock_opname'         => fake()->word(),
            'resignation_letter_url'         => fake()->url(),
            'form_uid'                       => fake()->bothify('EXC-#####'),
            'form_status'                    => fake()->randomElement(['Pending', 'Approved', 'Rejected']),
            'form_response_id'               => fake()->uuid(),
            'created_by'                     => null,
        ];
    }
}
