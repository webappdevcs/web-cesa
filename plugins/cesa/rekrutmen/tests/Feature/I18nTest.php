<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class I18nTest extends RekrutmenTestCase
{
    public function test_navigation_and_enum_labels_are_localized(): void
    {
        foreach ($this->localeExpectations() as $locale => $expected) {
            app()->setLocale($locale);

            $this->assertSame($expected['navigation_group'], RequestManPowerResource::getNavigationGroup());
            $this->assertSame($expected['navigation_label'], RequestManPowerResource::getNavigationLabel());
            $this->assertSame($expected['status_kebutuhan'], StatusKebutuhan::NEW_HIRING->getLabel());
            $this->assertSame($expected['request_status'], RequestManPowerStatus::PENDING->getLabel());
            $this->assertSame($expected['application_status'], JobApplicationStatus::IN_PROGRESS->getLabel());
            $this->assertSame($expected['job_level'], RequestManPower::getTranslatedLevelPekerjaanOptions()['Staff']);
            $this->assertSame($expected['public_progress_heading'], __('rekrutmen::app.public_progress.heading'));
            $this->assertSame($expected['mail_progress_action'], __('rekrutmen::app.mail.request_man_power_submitted.view_progress'));
        }
    }

    public function test_job_detail_api_returns_localized_messages_and_application_form_labels(): void
    {
        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'   => null,
            'rekrutmen_pipeline_id'  => null,
            'title'                  => 'Backend Developer',
            'slug'                   => 'backend-developer',
            'description'            => 'Build APIs and internal tools.',
            'requirements'           => 'Laravel and SQL',
            'location'               => 'Jakarta',
            'is_published'           => true,
            'closing_date'           => null,
        ]);

        foreach ($this->localeExpectations() as $locale => $expected) {
            app()->setLocale($locale);

            $response = app(CareerController::class)->show($jobPosting->slug);
            $payload = $response->getData(true);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame($expected['job_detail_message'], $payload['message']);
            $this->assertSame($expected['application_form_full_name'], $payload['data']['application_form'][0]['label']);
            $this->assertSame($expected['application_form_portfolio'], $payload['data']['application_form'][3]['label']);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function localeExpectations(): array
    {
        return [
            'en' => [
                'navigation_group'             => 'Recruitment',
                'navigation_label'             => 'Manpower Requests',
                'status_kebutuhan'             => 'New Hiring',
                'request_status'               => 'Pending',
                'application_status'           => 'In Progress',
                'job_level'                    => 'Staff',
                'public_progress_heading'      => 'Manpower Request Progress',
                'mail_progress_action'         => 'View Submission Progress',
                'job_detail_message'           => 'Job detail retrieved successfully.',
                'application_form_full_name'   => 'Full Name',
                'application_form_portfolio'   => 'Portfolio URL',
            ],
            'id' => [
                'navigation_group'             => 'Rekrutmen',
                'navigation_label'             => 'Permintaan Tenaga Kerja',
                'status_kebutuhan'             => 'Karyawan Baru',
                'request_status'               => 'Pending',
                'application_status'           => 'Dalam Proses',
                'job_level'                    => 'Staf',
                'public_progress_heading'      => 'Progress Permintaan Tenaga Kerja',
                'mail_progress_action'         => 'Lihat Progress Pengajuan',
                'job_detail_message'           => 'Detail lowongan berhasil diambil.',
                'application_form_full_name'   => 'Nama Lengkap',
                'application_form_portfolio'   => 'URL Portofolio',
            ],
        ];
    }
}
