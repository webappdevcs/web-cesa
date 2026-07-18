<?php

namespace Cesa\Kepegawaian\Database\Factories;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeIdentifierFactory extends Factory
{
    protected $model = EmployeeIdentifier::class;

    public function definition(): array
    {
        return [
            'employee_id'     => Employee::factory(),
            'source_system'   => 'manual',
            'source_instance' => 'primary',
            'identifier_type' => 'record_id',
            'external_id'     => fake()->unique()->uuid(),
            'metadata'        => null,
            'verified_at'     => null,
            'last_seen_at'    => now(),
            'retired_at'      => null,
            'creator_id'      => null,
        ];
    }

    public function vendorRecord(): static
    {
        return $this->state(fn (): array => [
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (): array => [
            'retired_at' => now(),
        ]);
    }
}
