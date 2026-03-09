<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Storage;

class JobApplicationAttachmentLifecycleTest extends RekrutmenTestCase
{
    public function test_resume_is_renamed_replaced_and_removed(): void
    {
        Storage::fake('local');

        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        $jobPosting = $this->createJobPosting();

        Storage::disk('local')->put('rekrutmen/cv/tmp-resume-a.pdf', 'resume-a');

        $application = JobApplication::query()->create([
            'job_posting_id' => $jobPosting->id,
            'full_name'      => 'Alice Candidate',
            'email'          => 'alice@example.com',
            'phone'          => '081234567890',
            'resume_path'    => 'rekrutmen/cv/tmp-resume-a.pdf',
            'status'         => JobApplicationStatus::IN_PROGRESS,
        ])->fresh();

        $this->assertNotNull($application);
        $this->assertMatchesRegularExpression(
            '#^rekrutmen/cv/CV-'.$application->id.'-software-engineer\\.pdf$#',
            (string) $application->resume_path
        );

        $firstStoredPath = (string) $application->resume_path;

        Storage::disk('local')->assertMissing('rekrutmen/cv/tmp-resume-a.pdf');
        Storage::disk('local')->assertExists($firstStoredPath);

        Storage::disk('local')->put('rekrutmen/cv/tmp-resume-b.pdf', 'resume-b');

        $application->update([
            'resume_path' => 'rekrutmen/cv/tmp-resume-b.pdf',
        ]);

        $application->refresh();

        $this->assertSame($firstStoredPath, $application->resume_path);
        Storage::disk('local')->assertMissing('rekrutmen/cv/tmp-resume-b.pdf');
        Storage::disk('local')->assertExists((string) $application->resume_path);

        $secondStoredPath = (string) $application->resume_path;

        $application->update([
            'resume_path' => null,
        ]);

        $application->refresh();

        $this->assertNull($application->resume_path);
        Storage::disk('local')->assertMissing($secondStoredPath);
    }

    public function test_soft_delete_keeps_resume_until_force_delete(): void
    {
        Storage::fake('local');

        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        $jobPosting = $this->createJobPosting();

        Storage::disk('local')->put('rekrutmen/cv/tmp-resume.pdf', 'resume');

        $application = JobApplication::query()->create([
            'job_posting_id' => $jobPosting->id,
            'full_name'      => 'Bob Candidate',
            'email'          => 'bob@example.com',
            'phone'          => '081234567891',
            'resume_path'    => 'rekrutmen/cv/tmp-resume.pdf',
            'status'         => JobApplicationStatus::IN_PROGRESS,
        ])->fresh();

        $storedPath = (string) $application->resume_path;

        $application->delete();

        Storage::disk('local')->assertExists($storedPath);

        $application->forceDelete();

        Storage::disk('local')->assertMissing($storedPath);
    }

    private function createJobPosting(): JobPosting
    {
        return JobPosting::query()->create([
            'title'        => 'Software Engineer',
            'slug'         => 'software-engineer-'.str()->lower(str()->random(6)),
            'description'  => 'Build systems',
            'requirements' => 'PHP, Laravel',
            'location'     => 'Jakarta',
            'is_published' => true,
        ]);
    }
}
