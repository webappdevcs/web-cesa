<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers\EmployeeWorkflowRelationManager;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\RelationManagers\HrWorkflowTasksRelationManager;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Cesa\Kepegawaian\Policies\HrWorkflowRunPolicy;
use Cesa\Kepegawaian\Policies\HrWorkflowTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Webkul\Security\Models\User;

class HrWorkflowFilamentTest extends TestCase
{
    public function test_employee_and_workflow_resources_expose_the_operational_relationships(): void
    {
        $this->assertContains(EmployeeWorkflowRelationManager::class, EmployeeResource::getRelations());
        $this->assertContains(HrWorkflowTasksRelationManager::class, HrWorkflowRunResource::getRelations());
        $this->assertArrayHasKey('create', HrWorkflowTemplateResource::getPages());
        $this->assertArrayHasKey('edit', HrWorkflowTemplateResource::getPages());
        $this->assertArrayHasKey('view', HrWorkflowRunResource::getPages());
        $this->assertArrayNotHasKey('create', HrWorkflowRunResource::getPages());
    }

    public function test_workflow_policies_are_registered_and_use_dedicated_permissions(): void
    {
        Gate::policy(HrWorkflowTemplate::class, HrWorkflowTemplatePolicy::class);
        Gate::policy(HrWorkflowRun::class, HrWorkflowRunPolicy::class);

        $this->assertInstanceOf(HrWorkflowTemplatePolicy::class, Gate::getPolicyFor(HrWorkflowTemplate::class));
        $this->assertInstanceOf(HrWorkflowRunPolicy::class, Gate::getPolicyFor(HrWorkflowRun::class));

        $genericEmployeeEditor = $this->userWithAbilities(['update_kepegawaian_employee']);
        $workflowOperator = $this->userWithAbilities([
            'start_kepegawaian_hr::workflow::run',
            'manage_tasks_kepegawaian_hr::workflow::run',
            'cancel_kepegawaian_hr::workflow::run',
        ]);
        $run = new HrWorkflowRun(['status' => HrWorkflowRunStatus::InProgress]);

        $this->assertFalse((new HrWorkflowRunPolicy)->start($genericEmployeeEditor));
        $this->assertFalse((new HrWorkflowRunPolicy)->manageTasks($genericEmployeeEditor, $run));
        $this->assertTrue((new HrWorkflowRunPolicy)->start($workflowOperator));
        $this->assertTrue((new HrWorkflowRunPolicy)->manageTasks($workflowOperator, $run));
        $this->assertTrue((new HrWorkflowRunPolicy)->cancel($workflowOperator, $run));
    }

    public function test_workflow_permissions_are_declared_for_shield(): void
    {
        $shield = require base_path('plugins/cesa/kepegawaian/config/filament-shield.php');

        $this->assertSame(
            ['view_any', 'view', 'start', 'cancel', 'manage_tasks'],
            $shield['resources']['manage'][HrWorkflowRunResource::class],
        );
        $this->assertContains('create', $shield['resources']['manage'][HrWorkflowTemplateResource::class]);
        $this->assertContains('update', $shield['resources']['manage'][HrWorkflowTemplateResource::class]);
    }

    public function test_workflow_interface_is_translated_in_english_and_indonesian(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach ([
                'kepegawaian::filament/resources/hr-workflow-template.navigation',
                'kepegawaian::filament/resources/hr-workflow-run.navigation',
                'kepegawaian::filament/resources/employee/relation-manager/workflow.actions.start',
                'kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.actions.complete',
            ] as $key) {
                $this->assertNotSame($key, __($key));
            }
        }
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function userWithAbilities(array $abilities): User
    {
        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
        $user->abilities = $abilities;

        return $user;
    }
}
