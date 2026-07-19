<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class EmployeeSyncRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_employee::sync::run');
    }

    public function view(User $user, EmployeeSyncRun $run): bool
    {
        return $user->can('view_kepegawaian_employee::sync::run');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EmployeeSyncRun $run): bool
    {
        return false;
    }

    public function delete(User $user, EmployeeSyncRun $run): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, EmployeeSyncRun $run): bool
    {
        return false;
    }

    public function forceDelete(User $user, EmployeeSyncRun $run): bool
    {
        return false;
    }
}
