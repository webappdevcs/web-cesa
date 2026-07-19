<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTask;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Cesa\Kepegawaian\Services\HrWorkflowService;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Carbon;
use LogicException;
use Webkul\Security\Models\User;

class HrWorkflowServiceTest extends KepegawaianIdentityTestCase
{
    public function test_it_starts_an_employee_workflow_from_an_ordered_template_snapshot(): void
    {
        Carbon::setTestNow('2026-07-19 09:00:00');

        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate([
            [
                'name'                => 'Siapkan akun kerja',
                'department'          => 'IT',
                'sort_order'          => 20,
                'due_days'            => 2,
                'default_assignee_id' => $assignee->getKey(),
            ],
            [
                'name'       => 'Verifikasi dokumen',
                'department' => 'Personalia',
                'sort_order' => 10,
                'due_days'   => 0,
            ],
        ]);

        $run = app(HrWorkflowService::class)->start($template, $employee, $actor, [
            'source' => 'manual',
        ]);

        $this->assertSame(HrWorkflowRunStatus::InProgress, $run->status);
        $this->assertSame($employee->getKey(), $run->employee_id);
        $this->assertSame($actor->getKey(), $run->started_by);
        $this->assertSame(['source' => 'manual'], $run->context);

        $tasks = $run->tasks()->orderBy('sort_order')->get();

        $this->assertSame(['Verifikasi dokumen', 'Siapkan akun kerja'], $tasks->pluck('name')->all());
        $this->assertSame(['Personalia', 'IT'], $tasks->pluck('department')->all());
        $this->assertSame('2026-07-19 09:00:00', $tasks[0]->due_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-21 09:00:00', $tasks[1]->due_at?->format('Y-m-d H:i:s'));
        $this->assertSame($assignee->getKey(), $tasks[1]->assigned_to_id);
        $this->assertSame(HrWorkflowTaskStatus::Pending, $tasks[0]->status);
    }

    public function test_it_rejects_a_duplicate_active_workflow_for_the_same_employee_and_template(): void
    {
        $actor = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate();
        $service = app(HrWorkflowService::class);

        $service->start($template, $employee, $actor);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An active workflow already exists');

        $service->start($template, $employee, $actor);
    }

    public function test_completing_the_last_task_completes_the_workflow_and_records_the_actor(): void
    {
        $actor = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate();
        $service = app(HrWorkflowService::class);
        $run = $service->start($template, $employee, $actor);

        foreach ($run->tasks()->orderBy('sort_order')->get() as $task) {
            $service->completeTask($task, $actor, 'Selesai diverifikasi');
        }

        $run->refresh();

        $this->assertSame(HrWorkflowRunStatus::Completed, $run->status);
        $this->assertSame($actor->getKey(), $run->completed_by);
        $this->assertNotNull($run->completed_at);
        $this->assertNull($run->active_key);
        $this->assertSame(
            [HrWorkflowTaskStatus::Completed, HrWorkflowTaskStatus::Completed],
            $run->tasks()->orderBy('sort_order')->pluck('status')->all(),
        );
    }

    public function test_an_evidence_required_task_cannot_be_completed_without_evidence(): void
    {
        $actor = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate([
            [
                'name'              => 'Unggah kontrak',
                'department'        => 'Personalia',
                'sort_order'        => 10,
                'requires_evidence' => true,
            ],
        ]);
        $task = app(HrWorkflowService::class)
            ->start($template, $employee, $actor)
            ->tasks()
            ->firstOrFail();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Evidence is required');

        app(HrWorkflowService::class)->completeTask($task, $actor, 'Kontrak ditandatangani');
    }

    public function test_a_required_task_cannot_be_skipped(): void
    {
        $actor = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate();
        $task = app(HrWorkflowService::class)
            ->start($template, $employee, $actor)
            ->tasks()
            ->orderBy('sort_order')
            ->firstOrFail();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A required task cannot be skipped');

        app(HrWorkflowService::class)->skipTask($task, $actor, 'Tidak relevan');
    }

    public function test_a_cancelled_workflow_blocks_task_updates(): void
    {
        $actor = User::factory()->create();
        $employee = $this->createEmployee();
        $template = $this->createTemplate();
        $service = app(HrWorkflowService::class);
        $run = $service->start($template, $employee, $actor);
        $task = $run->tasks()->firstOrFail();

        $service->cancel($run, $actor, 'Kandidat batal bergabung');

        $this->assertSame(HrWorkflowRunStatus::Cancelled, $run->refresh()->status);
        $this->assertNull($run->active_key);
        $this->assertSame(
            [HrWorkflowTaskStatus::Cancelled],
            $run->tasks()->distinct()->pluck('status')->all(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only an active workflow can be changed');

        $service->completeTask($task, $actor, 'Tidak boleh berubah');
    }

    public function test_it_assigns_and_starts_a_pending_task_through_the_workflow_service(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $employee = $this->createEmployee();
        $service = app(HrWorkflowService::class);
        $task = $service
            ->start($this->createTemplate(), $employee, $actor)
            ->tasks()
            ->firstOrFail();

        $service->assignTask($task, $assignee, $actor);
        $service->beginTask($task, $assignee);

        $this->assertSame($assignee->getKey(), $task->refresh()->assigned_to_id);
        $this->assertSame(HrWorkflowTaskStatus::InProgress, $task->status);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $steps
     */
    private function createTemplate(?array $steps = null): HrWorkflowTemplate
    {
        $template = HrWorkflowTemplate::query()->create([
            'code'        => 'onboarding-'.fake()->unique()->numerify('####'),
            'name'        => 'Onboarding Karyawan',
            'type'        => HrWorkflowType::Onboarding,
            'description' => 'Checklist lintas divisi untuk karyawan baru.',
            'is_active'   => true,
        ]);

        foreach ($steps ?? [
            [
                'name'       => 'Verifikasi dokumen',
                'department' => 'Personalia',
                'sort_order' => 10,
            ],
            [
                'name'       => 'Serah terima perangkat',
                'department' => 'GA',
                'sort_order' => 20,
            ],
        ] as $step) {
            $template->steps()->create(array_merge([
                'due_days'            => 0,
                'is_required'         => true,
                'requires_evidence'   => false,
                'default_assignee_id' => null,
            ], $step));
        }

        return $template->refresh();
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name'          => fake()->name(),
            'employee_code' => fake()->unique()->numerify('WF-####'),
            'is_active'     => true,
        ]);
    }
}
