<?php

namespace Cesa\ExitClearance\Policies;

use Cesa\ExitClearance\Models\Request;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class RequestPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_exit_clearance_request');
    }

    public function view(User $user, Request $request): bool
    {
        return $user->can('view_exit_clearance_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_exit_clearance_request');
    }

    public function update(User $user, Request $request): bool
    {
        if (! $user->can('update_exit_clearance_request')) {
            return false;
        }

        return $this->hasAccess($user, $request);
    }

    public function delete(User $user, Request $request): bool
    {
        if (! $user->can('delete_exit_clearance_request')) {
            return false;
        }

        return $this->hasAccess($user, $request);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_exit_clearance_request');
    }

    public function forceDelete(User $user, Request $request): bool
    {
        if (! $user->can('force_delete_exit_clearance_request')) {
            return false;
        }

        return $this->hasAccess($user, $request);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_exit_clearance_request');
    }

    public function restore(User $user, Request $request): bool
    {
        if (! $user->can('restore_exit_clearance_request')) {
            return false;
        }

        return $this->hasAccess($user, $request);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_exit_clearance_request');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_exit_clearance_request');
    }
}
