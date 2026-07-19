<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class EmployeeSyncConflictPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_employee::sync::conflict');
    }

    public function view(User $user, EmployeeSyncConflict $conflict): bool
    {
        return $user->can('view_kepegawaian_employee::sync::conflict');
    }

    public function resolve(User $user, EmployeeSyncConflict $conflict): bool
    {
        return $conflict->status === 'open'
            && $user->can('resolve_kepegawaian_employee::sync::conflict');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EmployeeSyncConflict $conflict): bool
    {
        return false;
    }

    public function delete(User $user, EmployeeSyncConflict $conflict): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, EmployeeSyncConflict $conflict): bool
    {
        return false;
    }

    public function forceDelete(User $user, EmployeeSyncConflict $conflict): bool
    {
        return false;
    }
}
