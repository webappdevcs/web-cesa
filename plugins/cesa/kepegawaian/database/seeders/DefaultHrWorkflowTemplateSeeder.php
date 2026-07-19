<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultHrWorkflowTemplateSeeder extends Seeder
{
    public const ONBOARDING_CODE = 'employee-onboarding';

    public const OFFBOARDING_CODE = 'employee-offboarding';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->installTemplate(
                code: self::ONBOARDING_CODE,
                name: 'Onboarding Karyawan',
                type: HrWorkflowType::Onboarding,
                description: 'Checklist lintas divisi berdasarkan Checklist Karyawan IN pada DATABASE KARYAWAN.xlsx.',
                steps: $this->onboardingSteps(),
            );

            $this->installTemplate(
                code: self::OFFBOARDING_CODE,
                name: 'Offboarding Karyawan',
                type: HrWorkflowType::Offboarding,
                description: 'Checklist lintas divisi berdasarkan Checklist Karyawan OUT pada DATABASE KARYAWAN.xlsx.',
                steps: $this->offboardingSteps(),
            );
        });
    }

    /**
     * @param  array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>  $steps
     */
    private function installTemplate(
        string $code,
        string $name,
        HrWorkflowType $type,
        string $description,
        array $steps,
    ): void {
        $template = HrWorkflowTemplate::query()->firstOrCreate(
            ['code' => $code],
            [
                'name'        => $name,
                'type'        => $type,
                'description' => $description,
                'is_active'   => true,
            ],
        );

        foreach ($steps as $index => $step) {
            $template->steps()->firstOrCreate(
                [
                    'department' => $step['department'],
                    'name'       => $step['name'],
                ],
                [
                    'sort_order'        => ($index + 1) * 10,
                    'due_days'          => $step['due_days'],
                    'is_required'       => true,
                    'requires_evidence' => $step['requires_evidence'] ?? false,
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function onboardingSteps(): array
    {
        return [
            $this->step('CV', 'Recruitment', 0, true),
            $this->step('Application Form', 'Recruitment', 0, true),
            $this->step('Hasil Psikotes', 'Recruitment', 0, true),
            $this->step('Form Interview User', 'Recruitment', 0, true),
            $this->step('Background Check', 'Recruitment', 0),
            $this->step('Get Contact', 'Recruitment', 0),
            $this->step('Ijazah / BPKB', 'Recruitment', 0, true),
            $this->step('Offering Letter', 'Recruitment', 0, true),
            $this->step('Welcoming Text', 'Recruitment', 0),
            $this->step('Pengisian Data Karyawan', 'Recruitment', 0, true),
            $this->step('Input Data ke Talenta', 'Recruitment', 1),
            $this->step('SPK (PKWT)', 'Personalia', 1, true),
            $this->step('SPK (PKWTT)', 'Personalia', 1, true),
            $this->step('NDA (Perjanjian Kerahasiaan)', 'Personalia', 1, true),
            $this->step('Penjelasan Talenta', 'Personalia', 1),
            $this->step('Update Karyawan IN', 'Personalia', 1),
            $this->step('Company Culture', 'Training', 3),
            $this->step('Onboarding Karyawan Baru', 'Training', 3),
            $this->step('Pre & Post Test', 'Training', 5, true),
            $this->step('KPI Training', 'Training', 7, true),
            $this->step('Asset HP', 'GA', 1, true),
            $this->step('Asset Laptop', 'GA', 1, true),
            $this->step('Asset Lainnya', 'GA', 1, true),
            $this->step('ID Card', 'GA', 3, true),
            $this->step('Akses Parkir', 'GA', 3),
            $this->step('Note GA', 'GA', 3),
            $this->step('Dokumen Job Desk', 'Busdev', 2, true),
            $this->step('Email User', 'Busdev', 1),
            $this->step('Update Struktur Organisasi', 'Busdev', 3),
            $this->step('TTD Kontrak Kerja', 'HR Manager', 3, true),
        ];
    }

    /**
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function offboardingSteps(): array
    {
        return [
            $this->step('Update Karyawan OUT', 'Personalia', 0),
            $this->step('Cek Loan Karyawan', 'Personalia', 1, true),
            $this->step('Cek Cicilan Karyawan', 'Personalia', 1, true),
            $this->step('Exit Clearance Form', 'Personalia', 2, true),
            $this->step('Update Talenta', 'Personalia', 3),
            $this->step('Paklaring', 'Personalia', 5, true),
            $this->step('Asset HP', 'GA', 2, true),
            $this->step('Asset Laptop', 'GA', 2, true),
            $this->step('Asset Lainnya', 'GA', 2, true),
            $this->step('ID Card', 'GA', 2, true),
            $this->step('Akses Parkir', 'GA', 2),
            $this->step('Note GA', 'GA', 2),
            $this->step('Penonaktifan Email User', 'Busdev', 2),
            $this->step('Update Struktur Organisasi', 'Busdev', 3),
        ];
    }

    /**
     * @return array{name: string, department: string, due_days: int, requires_evidence?: bool}
     */
    private function step(
        string $name,
        string $department,
        int $dueDays,
        bool $requiresEvidence = false,
    ): array {
        return [
            'name'              => $name,
            'department'        => $department,
            'due_days'          => $dueDays,
            'requires_evidence' => $requiresEvidence,
        ];
    }
}
