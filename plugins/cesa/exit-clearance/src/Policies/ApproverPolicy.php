<?php

namespace Cesa\ExitClearance\Policies;

use Cesa\ExitClearance\Models\Approver;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class ApproverPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_exit_clearance_approver');
    }

    public function view(User $user, Approver $approver): bool
    {
        return $user->can('view_exit_clearance_approver');
    }

    public function create(User $user): bool
    {
        return $user->can('create_exit_clearance_approver');
    }

    public function update(User $user, Approver $approver): bool
    {
        if (! $user->can('update_exit_clearance_approver')) {
            return false;
        }

        return $this->hasAccess($user, $approver);
    }

    public function delete(User $user, Approver $approver): bool
    {
        if (! $user->can('delete_exit_clearance_approver')) {
            return false;
        }

        return $this->hasAccess($user, $approver);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_exit_clearance_approver');
    }

    public function forceDelete(User $user, Approver $approver): bool
    {
        if (! $user->can('force_delete_exit_clearance_approver')) {
            return false;
        }

        return $this->hasAccess($user, $approver);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_exit_clearance_approver');
    }

    public function restore(User $user, Approver $approver): bool
    {
        if (! $user->can('restore_exit_clearance_approver')) {
            return false;
        }

        return $this->hasAccess($user, $approver);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_exit_clearance_approver');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_exit_clearance_approver');
    }
}
