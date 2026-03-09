<?php

namespace Cesa\Rekrutmen\Database\Seeders;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RequestManPower;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RekrutmenDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([DatabaseSeeder::class]);

        $pipeline = \Cesa\Rekrutmen\Models\RekrutmenPipeline::query()
            ->where('name', 'Default Recruitment Pipeline')
            ->first();

        if (! $pipeline) {
            return;
        }

        $screeningStage = $pipeline->stages()->where('name', 'Screening CV')->first();
        $interviewHrStage = $pipeline->stages()->where('name', 'Interview HR')->first();

        if (! $screeningStage || ! $interviewHrStage) {
            return;
        }

        $requestManPower = RequestManPower::query()->firstOrCreate(
            [
                'email_address'     => 'hr.rekrutmen@example.com',
                'posisi_dibutuhkan' => 'Software Engineer',
            ],
            [
                'nama_pengaju'               => 'HR Rekrutmen',
                'posisi_pengaju'             => 'HR Manager',
                'tanggal_pengajuan'          => now()->toDateString(),
                'lokasi_penempatan'          => 'Jakarta',
                'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
                'divisi'                     => 'Technology',
                'level_pekerjaan'            => 'Staff',
                'nama_karyawan_replacement'  => null,
                'badan_usaha'                => 'PT CESA',
                'jumlah_karyawan_dibutuhkan' => 2,
                'estimasi_tanggal_join'      => now()->addMonth()->toDateString(),
                'requirements_kualifikasi'   => 'Pengalaman minimal 2 tahun di Laravel.',
                'job_description'            => 'Mengembangkan dan memelihara aplikasi internal.',
                'keterangan'                 => 'Prioritas tinggi.',
                'status'                     => RequestManPowerStatus::PENDING,
                'approved_by'                => null,
            ],
        );

        $jobPosting = JobPosting::query()->firstOrCreate(
            ['slug' => Str::slug('Software Engineer Jakarta')],
            [
                'request_man_power_id'  => $requestManPower->getKey(),
                'rekrutmen_pipeline_id' => $pipeline->getKey(),
                'title'                 => 'Software Engineer Jakarta',
                'description'           => 'Kesempatan bergabung dengan tim engineering CESA.',
                'requirements'          => 'Menguasai PHP, Laravel, MySQL.',
                'location'              => 'Jakarta',
                'is_published'          => true,
                'closing_date'          => now()->addWeeks(3)->toDateString(),
            ],
        );

        $jobApplication = JobApplication::query()->firstOrCreate(
            [
                'job_posting_id' => $jobPosting->getKey(),
                'email'          => 'kandidat@example.com',
            ],
            [
                'current_stage_id' => $interviewHrStage->getKey(),
                'full_name'        => 'Kandidat Rekrutmen',
                'phone'            => '081234567890',
                'resume_path'      => 'rekrutmen/cv/sample-cv.pdf',
                'cover_letter'     => 'Saya tertarik bergabung dan berkontribusi.',
                'portfolio_url'    => 'https://portfolio.example.com',
                'status'           => JobApplicationStatus::IN_PROGRESS,
            ],
        );

        JobApplicationHistory::query()->firstOrCreate(
            [
                'job_application_id' => $jobApplication->getKey(),
                'status'             => JobApplicationStatus::IN_PROGRESS,
                'from_stage_id'      => $screeningStage->getKey(),
                'to_stage_id'        => $interviewHrStage->getKey(),
            ],
            [
                'notes'        => 'Lolos screening dan lanjut interview HR.',
                'performed_by' => null,
            ],
        );
    }
}
