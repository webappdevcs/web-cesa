<?php

namespace Cesa\FormTransfer\Database\Factories;

use App\Models\User;
use Cesa\FormTransfer\Models\FormTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\Support\Models\Company;

class FormTransferFactory extends Factory
{
    protected $model = FormTransfer::class;

    public function definition(): array
    {
        $companyName = $this->faker->unique()->company();
        $name = 'Form Transfer '.$companyName;

        $prefix = Str::upper(Str::slug($companyName));
        $prefix = Str::of($prefix)->replace('-', '')->upper()->take(4)->toString();

        if (strlen($prefix) < 3) {
            $prefix = Str::upper(Str::random(3));
        }

        return [
            'company_id'                 => null, // Set to null by default for tests, can be overridden
            'creator_id'                 => User::factory(),
            'name'                       => $name,
            'code'                       => Str::upper(Str::slug($name, '_')),
            'uid_prefix'                 => $prefix,
            'uid_padding'                => 5,
            'uid_sequence'               => 0,
            'description'                => $this->faker->optional()->sentence(),
            'is_active'                  => $this->faker->boolean(90),
            'approver_mail_subject'      => $this->faker->optional()->sentence(),
            'approver_mail_greeting'     => $this->faker->optional()->sentence(),
            'approver_mail_action_text'  => $this->faker->optional()->words(3, true),
            'approver_mail_template'     => $this->faker->optional()->paragraph(),
            'requester_mail_subject'     => $this->faker->optional()->sentence(),
            'requester_mail_greeting'    => $this->faker->optional()->sentence(),
            'requester_mail_action_text' => $this->faker->optional()->words(3, true),
            'requester_mail_template'    => $this->faker->optional()->paragraph(),
            'approver_whatsapp_template' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the form transfer belongs to a company.
     */
    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company?->getKey() ?? Company::factory(),
        ]);
    }
}
