<?php

namespace Cesa\ExitClearance\Policies;

use Cesa\ExitClearance\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class DepartmentPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_exit_clearance_department');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('view_exit_clearance_department');
    }

    public function create(User $user): bool
    {
        return $user->can('create_exit_clearance_department');
    }

    public function update(User $user, Department $department): bool
    {
        if (! $user->can('update_exit_clearance_department')) {
            return false;
        }

        return $this->hasAccess($user, $department);
    }

    public function delete(User $user, Department $department): bool
    {
        if (! $user->can('delete_exit_clearance_department')) {
            return false;
        }

        return $this->hasAccess($user, $department);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_exit_clearance_department');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        if (! $user->can('force_delete_exit_clearance_department')) {
            return false;
        }

        return $this->hasAccess($user, $department);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_exit_clearance_department');
    }

    public function restore(User $user, Department $department): bool
    {
        if (! $user->can('restore_exit_clearance_department')) {
            return false;
        }

        return $this->hasAccess($user, $department);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_exit_clearance_department');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_exit_clearance_department');
    }
}
