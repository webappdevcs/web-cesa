<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Rekrutmen\Models\JobApplication;
use Webkul\Security\Models\User;

class RecruitmentLifecycleAdapter
{
    public function __construct(private readonly EmployeeLifecycleBridge $bridge) {}

    public function handle(int $jobApplicationId, ?int $performedBy = null): void
    {
        $application = JobApplication::query()
            ->with('jobPosting')
            ->findOrFail($jobApplicationId);
        $actor = $performedBy ? User::query()->find($performedBy) : null;

        $this->bridge->hireFromRecruitment([
            'name'       => $application->full_name,
            'email'      => $application->email,
            'phone'      => $application->whatsapp_number,
            'job_title'  => $application->jobPosting?->title,
            'birth_date' => $application->birth_date?->toDateString(),
            'gender'     => $application->gender?->value,
        ], $jobApplicationId, $actor);
    }
}
