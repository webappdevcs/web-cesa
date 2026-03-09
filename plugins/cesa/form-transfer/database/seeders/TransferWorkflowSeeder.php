<?php

namespace Cesa\FormTransfer\Database\Seeders;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferWorkflowSeeder extends Seeder
{
    /**
     * Demo workflow defaults.
     * These records are not intended to be seeded automatically in production.
     */
    protected array $flows = [
        'defaultFlow' => [
            [
                'email' => 'default.approver1@example.com',
                'name'  => 'Default Approver 1',
                'title' => 'Team Lead',
                'phone' => '628123456789',
            ],
            [
                'email' => 'default.approver2@example.com',
                'name'  => 'Default Approver 2',
                'title' => 'Manager',
                'phone' => '628123456790',
            ],
        ],
        'Online' => [
            [
                'email' => 'online.supervisor@example.com',
                'name'  => 'Online Supervisor',
                'title' => 'Online Supervisor',
                'phone' => '628123456791',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'AR' => [
            [
                'email' => 'ar.supervisor@example.com',
                'name'  => 'AR Supervisor',
                'title' => 'Supervisor',
                'phone' => '628123456793',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'DATA' => [
            [
                'email' => 'data.coordinator@example.com',
                'name'  => 'Data Coordinator',
                'title' => 'Data Coordinator',
                'phone' => '628123456794',
            ],
            [
                'email' => 'coo@example.com',
                'name'  => 'Chief Operating Officer',
                'title' => 'COO',
                'phone' => '628123456795',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'IT' => [
            [
                'email' => 'it.manager@example.com',
                'name'  => 'IT Manager',
                'title' => 'IT Manager',
                'phone' => '628123456796',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'Finance' => [
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'OfficePWK' => [
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'HR' => [
            [
                'email' => 'hr.manager@example.com',
                'name'  => 'HR Manager',
                'title' => 'HR Manager',
                'phone' => '628123456797',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'HRPLUS' => [
            [
                'email' => 'hr.manager@example.com',
                'name'  => 'HR Manager',
                'title' => 'HR Manager',
                'phone' => '628123456797',
            ],
            [
                'email' => 'ceo@example.com',
                'name'  => 'Chief Executive Officer',
                'title' => 'CEO',
                'phone' => '628123456798',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'AUDIT' => [
            [
                'email' => 'audit.manager@example.com',
                'name'  => 'Chief Internal Auditor',
                'title' => 'CIA',
                'phone' => '628123456799',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'TECNO' => [
            [
                'email' => 'sales.officer@example.com',
                'name'  => 'Chief Sales Officer',
                'title' => 'Chief Sales Officer',
                'phone' => '628123456800',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'ITEL' => [
            [
                'email' => 'sales.officer@example.com',
                'name'  => 'Chief Sales Officer',
                'title' => 'Chief Sales Officer',
                'phone' => '628123456800',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'Warehouse' => [
            [
                'email' => 'operations.manager@example.com',
                'name'  => 'Operations Manager',
                'title' => 'Manager Operational',
                'phone' => '628123456801',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'SCM' => [
            [
                'email' => 'scm.manager@example.com',
                'name'  => 'SCM Manager',
                'title' => 'SCM',
                'phone' => '628123456802',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'RSM' => [
            [
                'email' => 'regional.manager@example.com',
                'name'  => 'Regional Sales Manager',
                'title' => 'RSM',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
        'ZTE' => [
            [
                'email' => 'sales.officer@example.com',
                'name'  => 'Chief Sales Officer',
                'title' => 'Chief Sales Officer',
                'phone' => '628123456800',
            ],
            [
                'email' => 'finance.manager@example.com',
                'name'  => 'Finance Manager',
                'title' => 'Manager Finance',
                'phone' => '628123456792',
            ],
        ],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipping TransferWorkflowSeeder in production environment.');

            return;
        }

        DB::transaction(function (): void {
            $formTransfer = FormTransfer::query()->updateOrCreate(
                ['code' => 'MAJU_TRANSFER'],
                [
                    'company_id'   => null,
                    'name'         => 'Form Transfer Maju',
                    'uid_prefix'   => 'MAJU',
                    'uid_padding'  => 5,
                    'uid_sequence' => FormTransfer::query()
                        ->where('code', 'MAJU_TRANSFER')
                        ->value('uid_sequence') ?? 0,
                    'description'  => 'Form transfer internal yang digunakan oleh seluruh divisi.',
                    'is_active'    => true,
                ]
            );

            foreach ($this->flows as $divisionName => $approvers) {
                $division = $divisionName !== 'defaultFlow'
                    ? TransferDivision::query()->updateOrCreate(
                        [
                            'form_transfer_id' => $formTransfer->getKey(),
                            'code'             => Str::slug($divisionName, '_'),
                        ],
                        [
                            'name'        => $divisionName,
                            'description' => 'Divisi '.$divisionName,
                            'is_active'   => true,
                        ]
                    )
                    : null;

                TransferApprovalWorkflow::query()->updateOrCreate(
                    [
                        'form_transfer_id' => $formTransfer->getKey(),
                        'division_id'      => $division?->getKey(),
                        'code'             => $division ? Str::slug($divisionName, '_').'_workflow' : 'default_workflow',
                    ],
                    [
                        'name'        => $division ? 'Workflow '.$divisionName : 'Workflow Default',
                        'description' => $division
                            ? 'Workflow persetujuan untuk divisi '.$divisionName
                            : 'Workflow default digunakan jika divisi tidak memiliki konfigurasi khusus.',
                        'steps'       => $this->buildSteps($approvers),
                        'is_active'   => true,
                    ]
                );
            }
        });
    }

    protected function buildSteps(array $approvers): array
    {
        return collect($approvers)
            ->values()
            ->map(function (array $approver, int $index): array {
                $label = Arr::get($approver, 'title') ?? 'Approval '.($index + 1);

                return [
                    'label'          => $label,
                    'default_name'   => Arr::get($approver, 'name'),
                    'default_email'  => Arr::get($approver, 'email'),
                    'default_title'  => Arr::get($approver, 'title'),
                    'default_phone'  => Arr::get($approver, 'phone'),
                    'is_mandatory'   => true,
                    'sort_order'     => $index + 1,
                ];
            })
            ->all();
    }
}
