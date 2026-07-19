<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Services\EmployeeLifecycleBridge;
use Cesa\Kepegawaian\Services\HrWorkflowService;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Webkul\Security\Models\User;

class EmployeeLifecycleBridgeTest extends KepegawaianIdentityTestCase
{
    public function test_hired_candidate_becomes_an_employee_with_an_idempotent_onboarding_workflow(): void
    {
        $actor = User::factory()->create();
        $bridge = app(EmployeeLifecycleBridge::class);
        $candidate = [
            'name'       => 'Kandidat Diterima',
            'email'      => 'candidate.hired@example.com',
            'phone'      => '081234567890',
            'job_title'  => 'Area Sales Supervisor',
            'birth_date' => '1994-05-12',
            'gender'     => 'male',
        ];

        $firstRun = $bridge->hireFromRecruitment($candidate, 712, $actor);
        $secondRun = $bridge->hireFromRecruitment($candidate, 712, $actor);

        $employee = Employee::query()->where('private_email', 'candidate.hired@example.com')->firstOrFail();

        $this->assertSame($firstRun->getKey(), $secondRun->getKey());
        $this->assertSame($employee->getKey(), $firstRun->employee_id);
        $this->assertSame('Area Sales Supervisor', $employee->job_title);
        $this->assertSame(30, $firstRun->tasks()->count());
        $this->assertSame('rekrutmen:712:onboarding', $firstRun->source_key);
        $this->assertTrue(EmployeeIdentifier::query()
            ->whereBelongsTo($employee)
            ->where('source_system', 'cesa_rekrutmen')
            ->where('external_id', '712')
            ->exists());
    }

    public function test_approved_exit_request_starts_offboarding_and_deactivates_employee_after_all_tasks_finish(): void
    {
        $actor = User::factory()->create();
        $employee = Employee::query()->create([
            'name'          => 'Karyawan Resign',
            'employee_code' => 'EXIT-001',
            'private_email' => 'employee.exit@example.com',
            'is_active'     => true,
        ]);
        $bridge = app(EmployeeLifecycleBridge::class);

        $run = $bridge->startOffboardingFromExit([
            'name'           => 'Karyawan Resign',
            'email'          => 'employee.exit@example.com',
            'departure_date' => '2026-08-31',
            'reason'         => 'Mengundurkan diri.',
            'form_uid'       => 'EXC-00712',
        ], 712);

        $this->assertSame($employee->getKey(), $run->employee_id);
        $this->assertSame(14, $run->tasks()->count());
        $this->assertSame('exit_clearance:712:offboarding', $run->source_key);

        foreach ($run->tasks()->orderBy('sort_order')->get() as $task) {
            app(HrWorkflowService::class)->completeTask(
                $task,
                $actor,
                'Clearance selesai.',
                $task->requires_evidence ? 'hr-workflow-evidence/test.pdf' : null,
            );
        }

        $this->assertSame(HrWorkflowRunStatus::Completed, $run->refresh()->status);
        $this->assertFalse($employee->refresh()->is_active);
        $this->assertSame('2026-08-31', $employee->departure_date);
        $this->assertSame('Mengundurkan diri.', $employee->departure_description);
    }
}
