<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\DefaultHrWorkflowTemplateSeeder;
use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers\EmployeeWorkflowRelationManager;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\Pages\ViewHrWorkflowRun;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\RelationManagers\HrWorkflowTasksRelationManager;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Livewire\Livewire;
use Webkul\Security\Models\User;

class HrWorkflowFilamentInteractionTest extends KepegawaianIdentityTestCase
{
    public function test_hr_operator_can_start_and_execute_employee_tasks_from_relation_managers(): void
    {
        $employee = Employee::query()->create([
            'name'          => 'Karyawan Workflow UI',
            'employee_code' => 'WF-UI-001',
            'is_active'     => true,
        ]);
        $actor = $this->actor([
            'view_any_kepegawaian_employee',
            'view_kepegawaian_employee',
            'view_any_kepegawaian_hr::workflow::run',
            'view_kepegawaian_hr::workflow::run',
            'start_kepegawaian_hr::workflow::run',
            'manage_tasks_kepegawaian_hr::workflow::run',
        ]);
        $this->actingAs($actor);

        $template = HrWorkflowTemplate::query()
            ->where('code', DefaultHrWorkflowTemplateSeeder::ONBOARDING_CODE)
            ->firstOrFail();

        Livewire::test(EmployeeWorkflowRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass'   => ViewEmployee::class,
        ])
            ->assertTableActionVisible('start')
            ->callTableAction('start', data: [
                'template_id'     => $template->getKey(),
                'reference_number'=> 'ONB-2026-0001',
                'effective_date'  => '2026-08-01',
                'expiry_date'     => '2027-07-31',
                'notes'           => 'Mulai kerja sesuai offering final.',
            ])
            ->assertNotified();

        $run = HrWorkflowRun::query()->whereBelongsTo($employee)->firstOrFail();
        $this->assertSame('ONB-2026-0001', $run->context['reference_number']);
        $this->assertSame('2026-08-01', $run->context['effective_date']);
        $this->assertSame('2027-07-31', $run->context['expiry_date']);
        $this->assertSame('Mulai kerja sesuai offering final.', $run->context['notes']);
        $task = $run->tasks()->where('requires_evidence', false)->orderBy('sort_order')->firstOrFail();

        Livewire::test(HrWorkflowTasksRelationManager::class, [
            'ownerRecord' => $run,
            'pageClass'   => ViewHrWorkflowRun::class,
        ])
            ->assertTableActionVisible('assign', $task)
            ->callTableAction('assign', $task, ['assigned_to_id' => $actor->getKey()])
            ->callTableAction('begin', $task)
            ->callTableAction('complete', $task, ['note' => 'Diselesaikan dari UI CESA.'])
            ->assertNotified();

        $task->refresh();

        $this->assertSame($actor->getKey(), $task->assigned_to_id);
        $this->assertSame(HrWorkflowTaskStatus::Completed, $task->status);
        $this->assertSame('Diselesaikan dari UI CESA.', $task->completion_note);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function actor(array $abilities): User
    {
        $persisted = User::factory()->create();
        $actor = new class extends User
        {
            /** @var array<int, string> */
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
        $actor->id = $persisted->getKey();
        $actor->exists = true;
        $actor->abilities = $abilities;

        return $actor;
    }
}
