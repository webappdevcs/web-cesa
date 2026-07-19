<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTask;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Webkul\Security\Models\User;

class HrWorkflowService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function start(
        HrWorkflowTemplate $template,
        Employee $employee,
        ?User $actor,
        array $context = [],
    ): HrWorkflowRun {
        $this->ensurePersisted($template, $employee);

        if ($actor !== null) {
            $this->ensurePersisted($actor);
        }

        try {
            return DB::transaction(function () use ($template, $employee, $actor, $context): HrWorkflowRun {
                $lockedTemplate = HrWorkflowTemplate::query()
                    ->with(['steps' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
                    ->lockForUpdate()
                    ->findOrFail($template->getKey());

                if (! $lockedTemplate->is_active) {
                    throw new LogicException('Only an active workflow template can be started.');
                }

                if ($lockedTemplate->steps->isEmpty()) {
                    throw new LogicException('A workflow template must contain at least one task.');
                }

                $activeKey = hash('sha256', $employee->getKey().'|'.$lockedTemplate->getKey());
                $sourceKey = isset($context['source_key']) && is_string($context['source_key'])
                    ? trim($context['source_key'])
                    : null;

                if (HrWorkflowRun::query()->where('active_key', $activeKey)->exists()) {
                    throw new LogicException('An active workflow already exists for this employee and template.');
                }

                if ($sourceKey !== null && HrWorkflowRun::query()->where('source_key', $sourceKey)->exists()) {
                    throw new LogicException('This source lifecycle event has already been processed.');
                }

                $startedAt = now();
                $run = HrWorkflowRun::query()->create([
                    'template_id'  => $lockedTemplate->getKey(),
                    'employee_id'  => $employee->getKey(),
                    'template_code'=> $lockedTemplate->code,
                    'name'         => $lockedTemplate->name,
                    'type'         => $lockedTemplate->type,
                    'status'       => HrWorkflowRunStatus::InProgress,
                    'active_key'   => $activeKey,
                    'source_key'   => $sourceKey,
                    'context'      => $context,
                    'started_at'   => $startedAt,
                    'due_at'       => $startedAt->copy()->addDays($lockedTemplate->steps->max('due_days')),
                    'started_by'   => $actor?->getKey(),
                ]);

                foreach ($lockedTemplate->steps as $step) {
                    $run->tasks()->create([
                        'template_step_id'  => $step->getKey(),
                        'sort_order'        => $step->sort_order,
                        'name'              => $step->name,
                        'department'        => $step->department,
                        'status'            => HrWorkflowTaskStatus::Pending,
                        'is_required'       => $step->is_required,
                        'requires_evidence' => $step->requires_evidence,
                        'assigned_to_id'    => $step->default_assignee_id,
                        'due_at'            => $startedAt->copy()->addDays($step->due_days),
                    ]);
                }

                Log::info('HR workflow started', [
                    'workflow_run_id' => $run->getKey(),
                    'template_id'     => $lockedTemplate->getKey(),
                    'employee_id'     => $employee->getKey(),
                    'actor_id'        => $actor?->getKey(),
                ]);

                return $run->load('tasks');
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new LogicException(
                    'An active workflow already exists for this employee and template.',
                    previous: $exception,
                );
            }

            throw $exception;
        }
    }

    public function completeTask(
        HrWorkflowTask $task,
        User $actor,
        ?string $note = null,
        ?string $evidencePath = null,
    ): HrWorkflowTask {
        $this->ensurePersisted($task, $actor);

        return DB::transaction(function () use ($task, $actor, $note, $evidencePath): HrWorkflowTask {
            $lockedTask = HrWorkflowTask::query()->lockForUpdate()->findOrFail($task->getKey());
            $run = HrWorkflowRun::query()->lockForUpdate()->findOrFail($lockedTask->run_id);

            $this->ensureRunIsActive($run);

            if ($lockedTask->requires_evidence && blank($evidencePath) && blank($lockedTask->evidence_path)) {
                throw new LogicException('Evidence is required before this task can be completed.');
            }

            if (! in_array($lockedTask->status, [HrWorkflowTaskStatus::Pending, HrWorkflowTaskStatus::InProgress], true)) {
                throw new LogicException('Only a pending or in-progress task can be completed.');
            }

            $lockedTask->update([
                'status'          => HrWorkflowTaskStatus::Completed,
                'completed_at'    => now(),
                'completed_by'    => $actor->getKey(),
                'completion_note' => $note,
                'evidence_path'   => $evidencePath ?? $lockedTask->evidence_path,
            ]);

            $this->completeRunIfReady($run, $actor);

            Log::info('HR workflow task completed', [
                'workflow_run_id'  => $run->getKey(),
                'workflow_task_id' => $lockedTask->getKey(),
                'actor_id'         => $actor->getKey(),
            ]);

            return $lockedTask->refresh();
        });
    }

    public function assignTask(HrWorkflowTask $task, User $assignee, User $actor): HrWorkflowTask
    {
        $this->ensurePersisted($task, $assignee, $actor);

        return DB::transaction(function () use ($task, $assignee, $actor): HrWorkflowTask {
            $lockedTask = HrWorkflowTask::query()->lockForUpdate()->findOrFail($task->getKey());
            $run = HrWorkflowRun::query()->lockForUpdate()->findOrFail($lockedTask->run_id);

            $this->ensureRunIsActive($run);

            if (! in_array($lockedTask->status, [HrWorkflowTaskStatus::Pending, HrWorkflowTaskStatus::InProgress], true)) {
                throw new LogicException('Only an active task can be assigned.');
            }

            $lockedTask->update(['assigned_to_id' => $assignee->getKey()]);

            Log::info('HR workflow task assigned', [
                'workflow_run_id'  => $run->getKey(),
                'workflow_task_id' => $lockedTask->getKey(),
                'assignee_id'      => $assignee->getKey(),
                'actor_id'         => $actor->getKey(),
            ]);

            return $lockedTask->refresh();
        });
    }

    public function beginTask(HrWorkflowTask $task, User $actor): HrWorkflowTask
    {
        $this->ensurePersisted($task, $actor);

        return DB::transaction(function () use ($task, $actor): HrWorkflowTask {
            $lockedTask = HrWorkflowTask::query()->lockForUpdate()->findOrFail($task->getKey());
            $run = HrWorkflowRun::query()->lockForUpdate()->findOrFail($lockedTask->run_id);

            $this->ensureRunIsActive($run);

            if ($lockedTask->status !== HrWorkflowTaskStatus::Pending) {
                throw new LogicException('Only a pending task can be started.');
            }

            $lockedTask->update([
                'status'         => HrWorkflowTaskStatus::InProgress,
                'assigned_to_id' => $lockedTask->assigned_to_id ?? $actor->getKey(),
            ]);

            return $lockedTask->refresh();
        });
    }

    public function skipTask(HrWorkflowTask $task, User $actor, string $reason): HrWorkflowTask
    {
        $this->ensurePersisted($task, $actor);

        return DB::transaction(function () use ($task, $actor, $reason): HrWorkflowTask {
            $lockedTask = HrWorkflowTask::query()->lockForUpdate()->findOrFail($task->getKey());
            $run = HrWorkflowRun::query()->lockForUpdate()->findOrFail($lockedTask->run_id);

            $this->ensureRunIsActive($run);

            if ($lockedTask->is_required) {
                throw new LogicException('A required task cannot be skipped.');
            }

            if (! in_array($lockedTask->status, [HrWorkflowTaskStatus::Pending, HrWorkflowTaskStatus::InProgress], true)) {
                throw new LogicException('Only a pending or in-progress task can be skipped.');
            }

            $lockedTask->update([
                'status'          => HrWorkflowTaskStatus::Skipped,
                'completed_at'    => now(),
                'completed_by'    => $actor->getKey(),
                'completion_note' => $reason,
            ]);

            $this->completeRunIfReady($run, $actor);

            return $lockedTask->refresh();
        });
    }

    public function cancel(HrWorkflowRun $run, User $actor, string $reason): HrWorkflowRun
    {
        $this->ensurePersisted($run, $actor);

        return DB::transaction(function () use ($run, $actor, $reason): HrWorkflowRun {
            $lockedRun = HrWorkflowRun::query()->lockForUpdate()->findOrFail($run->getKey());
            $this->ensureRunIsActive($lockedRun);

            $lockedRun->tasks()
                ->whereIn('status', [HrWorkflowTaskStatus::Pending, HrWorkflowTaskStatus::InProgress])
                ->update(['status' => HrWorkflowTaskStatus::Cancelled, 'updated_at' => now()]);

            $lockedRun->update([
                'status'              => HrWorkflowRunStatus::Cancelled,
                'active_key'          => null,
                'cancelled_at'        => now(),
                'cancelled_by'        => $actor->getKey(),
                'cancellation_reason' => $reason,
            ]);

            Log::notice('HR workflow cancelled', [
                'workflow_run_id' => $lockedRun->getKey(),
                'employee_id'     => $lockedRun->employee_id,
                'actor_id'        => $actor->getKey(),
                'reason'          => $reason,
            ]);

            return $lockedRun->refresh();
        });
    }

    private function completeRunIfReady(HrWorkflowRun $run, User $actor): void
    {
        $unfinished = $run->tasks()
            ->whereNotIn('status', [HrWorkflowTaskStatus::Completed, HrWorkflowTaskStatus::Skipped])
            ->exists();

        if ($unfinished) {
            return;
        }

        $run->update([
            'status'       => HrWorkflowRunStatus::Completed,
            'active_key'   => null,
            'completed_at' => now(),
            'completed_by' => $actor->getKey(),
        ]);

        if ($run->type === HrWorkflowType::Offboarding
            && ($run->context['deactivate_employee_on_completion'] ?? false) === true) {
            $run->employee()->update([
                'is_active'             => false,
                'departure_date'        => $run->context['departure_date'] ?? now()->toDateString(),
                'departure_description' => $run->context['departure_reason'] ?? null,
            ]);
        }
    }

    private function ensureRunIsActive(HrWorkflowRun $run): void
    {
        if ($run->status !== HrWorkflowRunStatus::InProgress) {
            throw new LogicException('Only an active workflow can be changed.');
        }
    }

    private function ensurePersisted(Model ...$models): void
    {
        foreach ($models as $model) {
            if (! $model->exists) {
                throw new LogicException('Workflow operations require persisted records.');
            }
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000'], true);
    }
}
