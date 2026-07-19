<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class HrWorkflowRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_hr::workflow::run');
    }

    public function view(User $user, HrWorkflowRun $run): bool
    {
        return $user->can('view_kepegawaian_hr::workflow::run');
    }

    public function start(User $user): bool
    {
        return $user->can('start_kepegawaian_hr::workflow::run');
    }

    public function manageTasks(User $user, HrWorkflowRun $run): bool
    {
        return $user->can('manage_tasks_kepegawaian_hr::workflow::run');
    }

    public function cancel(User $user, HrWorkflowRun $run): bool
    {
        return $run->status === HrWorkflowRunStatus::InProgress
            && $user->can('cancel_kepegawaian_hr::workflow::run');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, HrWorkflowRun $run): bool
    {
        return false;
    }

    public function delete(User $user, HrWorkflowRun $run): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, HrWorkflowRun $run): bool
    {
        return false;
    }

    public function forceDelete(User $user, HrWorkflowRun $run): bool
    {
        return false;
    }
}
