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

    public const OFFERING_CODE = 'employee-offering';

    public const CONTRACT_RENEWAL_CODE = 'employee-contract-renewal';

    public const DISCIPLINARY_CODE = 'employee-disciplinary-action';

    public const TRAINING_CODE = 'employee-training';

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

            $this->installTemplate(
                code: self::OFFERING_CODE,
                name: 'Offering Karyawan',
                type: HrWorkflowType::Offering,
                description: 'Registrasi dan aktivitas offering berdasarkan No Offering 2026 pada DATABASE KARYAWAN.xlsx.',
                steps: $this->offeringSteps(),
            );

            $this->installTemplate(
                code: self::CONTRACT_RENEWAL_CODE,
                name: 'Perpanjangan Kontrak',
                type: HrWorkflowType::ContractRenewal,
                description: 'Monitoring dan aktivitas perpanjangan kontrak berdasarkan Update Perpanjangan Kontrak.',
                steps: $this->contractRenewalSteps(),
            );

            $this->installTemplate(
                code: self::DISCIPLINARY_CODE,
                name: 'Surat Peringatan Karyawan',
                type: HrWorkflowType::Disciplinary,
                description: 'Penerbitan dan tindak lanjut surat peringatan berdasarkan register SURAT PERINGATAN.',
                steps: $this->disciplinarySteps(),
            );

            $this->installTemplate(
                code: self::TRAINING_CODE,
                name: 'Training Karyawan',
                type: HrWorkflowType::Training,
                description: 'Pelaksanaan dan evaluasi training berdasarkan register TRAINING 2025.',
                steps: $this->trainingSteps(),
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
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function offeringSteps(): array
    {
        return [
            $this->step('Validasi Komponen Offering', 'Recruitment', 0, true),
            $this->step('Persetujuan Offering', 'HR Manager', 1, true),
            $this->step('Terbitkan Offering Letter', 'Recruitment', 1, true),
            $this->step('Kirim Offering ke Kandidat', 'Recruitment', 1),
            $this->step('Catat Respons Kandidat', 'Recruitment', 3, true),
            $this->step('Arsipkan Offering Final', 'Personalia', 3, true),
        ];
    }

    /**
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function contractRenewalSteps(): array
    {
        return [
            $this->step('Review Tanggal Berakhir Kontrak', 'Personalia', 0, true),
            $this->step('Evaluasi Kinerja Karyawan', 'HR Manager', 3, true),
            $this->step('Konfirmasi Rekomendasi User', 'HR Manager', 5, true),
            $this->step('Persetujuan Perpanjangan', 'HR Manager', 7, true),
            $this->step('Siapkan Dokumen Kontrak', 'Personalia', 10, true),
            $this->step('Tanda Tangan Kontrak', 'Personalia', 14, true),
            $this->step('Update Masa Kontrak Karyawan', 'Personalia', 14, true),
        ];
    }

    /**
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function disciplinarySteps(): array
    {
        return [
            $this->step('Dokumentasikan Pelanggaran', 'Personalia', 0, true),
            $this->step('Klarifikasi dengan Karyawan', 'Personalia', 2, true),
            $this->step('Review Riwayat Surat Peringatan', 'Personalia', 2, true),
            $this->step('Persetujuan Surat Peringatan', 'HR Manager', 3, true),
            $this->step('Terbitkan Surat Peringatan', 'Personalia', 3, true),
            $this->step('Tanda Terima Karyawan', 'Personalia', 5, true),
            $this->step('Jadwalkan Evaluasi Tindak Lanjut', 'HR Manager', 30),
        ];
    }

    /**
     * @return array<int, array{name: string, department: string, due_days: int, requires_evidence?: bool}>
     */
    private function trainingSteps(): array
    {
        return [
            $this->step('Tetapkan Tujuan dan Materi Training', 'Training', 0, true),
            $this->step('Tetapkan Peserta dan Trainer', 'Training', 1, true),
            $this->step('Laksanakan Pre-Test', 'Training', 2, true),
            $this->step('Catat Kehadiran dan Materi', 'Training', 3, true),
            $this->step('Laksanakan Post-Test', 'Training', 3, true),
            $this->step('Evaluasi KPI Training', 'Training', 7, true),
            $this->step('Arsipkan Hasil dan Sertifikat', 'Training', 7, true),
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
