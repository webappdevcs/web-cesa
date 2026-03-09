<?php

namespace Cesa\FormTransfer\Database\Seeders;

use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TransferRequestSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure master data exists (idempotent).
        $this->call([
            TransferBankSeeder::class,
        ]);

        $formTransfer = FormTransfer::query()
            ->where('code', 'MAJU_TRANSFER')
            ->first()
            ?? FormTransfer::query()->first()
            ?? FormTransfer::factory()->create([
                'code'         => 'MAJU_TRANSFER',
                'name'         => 'Form Transfer Maju',
                'uid_prefix'   => 'MAJU',
                'uid_padding'  => 5,
                'uid_sequence' => 0,
                'is_active'    => true,
            ]);

        $bankIds = TransferBank::query()->pluck('id')->all();

        $divisionIds = TransferDivision::query()
            ->where('form_transfer_id', $formTransfer->getKey())
            ->pluck('id')
            ->all();

        $userIds = DB::table('users')->pluck('id')->all();

        if ($userIds === []) {
            $userIds[] = DB::table('users')->insertGetId([
                'name'              => 'Seeder User',
                'email'             => 'seeder+'.Str::uuid().'@example.test',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $faker = fake();

        $scenarios = [
            // Finance needs realization: approved + pending realization.
            [
                'submission_status'  => TransferRequestSubmissionStatus::BARU->value,
                'approval_status'    => TransferRequestApprovalStatus::APPROVED->value,
                'realization_status' => TransferRequestRealizationStatus::PENDING->value,
                'weight'             => 35,
            ],
            // Awaiting approval: pending approval + pending realization.
            [
                'submission_status'  => TransferRequestSubmissionStatus::BARU->value,
                'approval_status'    => TransferRequestApprovalStatus::PENDING->value,
                'realization_status' => TransferRequestRealizationStatus::PENDING->value,
                'weight'             => 35,
            ],
            // Realized.
            [
                'submission_status'  => TransferRequestSubmissionStatus::BARU->value,
                'approval_status'    => TransferRequestApprovalStatus::APPROVED->value,
                'realization_status' => TransferRequestRealizationStatus::DONE->value,
                'weight'             => 15,
            ],
            // Revisi.
            [
                'submission_status'  => TransferRequestSubmissionStatus::REVISI->value,
                'approval_status'    => TransferRequestApprovalStatus::PENDING->value,
                'realization_status' => TransferRequestRealizationStatus::PENDING->value,
                'weight'             => 10,
            ],
            // Rejected.
            [
                'submission_status'  => TransferRequestSubmissionStatus::BARU->value,
                'approval_status'    => TransferRequestApprovalStatus::REJECTED->value,
                'realization_status' => TransferRequestRealizationStatus::CANCELLED->value,
                'weight'             => 5,
            ],
        ];

        $weightedScenarios = [];

        foreach ($scenarios as $scenario) {
            $weightedScenarios = array_merge(
                $weightedScenarios,
                array_fill(0, $scenario['weight'], Arr::except($scenario, ['weight']))
            );
        }

        $count = 50;

        for ($i = 0; $i < $count; $i++) {
            $scenario = $weightedScenarios[array_rand($weightedScenarios)];
            $userId = $userIds[array_rand($userIds)];

            $divisionId = null;
            $divisionName = $faker->randomElement([
                'Finance',
                'Operations',
                'Marketing',
                'IT',
                'HR',
                'Warehouse',
                'SCM',
                'AR',
                'Online',
            ]);

            if (($divisionIds !== []) && $faker->boolean(70)) {
                $divisionId = $divisionIds[array_rand($divisionIds)];
                $divisionName = null;
            }

            $invoiceAttachment = $faker->boolean(60)
                ? ($faker->boolean(30)
                    ? [
                        $faker->lexify('documents/invoices/INV-????.pdf'),
                        $faker->lexify('documents/invoices/INV-????.pdf'),
                    ]
                    : $faker->lexify('documents/invoices/INV-????.pdf'))
                : [];

            $accountAttachment = $faker->boolean(50)
                ? ($faker->boolean(20)
                    ? [
                        $faker->lexify('documents/accounts/ACC-????.pdf'),
                        $faker->lexify('documents/accounts/ACC-????.pdf'),
                    ]
                    : $faker->lexify('documents/accounts/ACC-????.pdf'))
                : [];

            $attributes = array_merge($scenario, [
                'form_transfer_id'         => $formTransfer->getKey(),
                'user_id'                  => $userId,
                'creator_id'               => $userId,
                'bank_id'                  => $bankIds !== [] ? Arr::random($bankIds) : TransferBank::factory(),
                'division_id'              => $divisionId,
                'division_name'            => $divisionName,
                'reference_note'           => $faker->optional(0.6)->sentence(),
                'invoice_path'             => $invoiceAttachment,
                'account_attachment_path'  => $accountAttachment,
                'realized_at'              => null,
                'realization_proof_path'   => null,
                'realization_notes'        => $faker->optional(0.4)->sentence(),
            ]);

            if ($scenario['realization_status'] === TransferRequestRealizationStatus::DONE->value) {
                $attributes['realized_at'] = $faker->dateTimeBetween('-30 days')->format('Y-m-d');
                $attributes['realization_proof_path'] = $faker->lexify('documents/realizations/REAL-????.pdf');
                $attributes['realization_notes'] = $faker->optional(0.7)->sentence();
            }

            TransferRequest::factory()->create($attributes);
        }
    }
}
