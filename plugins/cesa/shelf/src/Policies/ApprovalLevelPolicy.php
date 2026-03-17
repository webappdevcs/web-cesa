<?php

namespace Cesa\Shelf\Policies;

use Cesa\Shelf\Models\ApprovalLevel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class ApprovalLevelPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shelf_approval::level');
    }

    public function view(User $user, ApprovalLevel $approvalLevel): bool
    {
        if (! $user->can('view_shelf_approval::level')) {
            return false;
        }

        return $this->hasAccess($user, $approvalLevel, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shelf_approval::level');
    }

    public function update(User $user, ApprovalLevel $approvalLevel): bool
    {
        if (! $user->can('update_shelf_approval::level')) {
            return false;
        }

        return $this->hasAccess($user, $approvalLevel, 'creator');
    }

    public function delete(User $user, ApprovalLevel $approvalLevel): bool
    {
        if (! $user->can('delete_shelf_approval::level')) {
            return false;
        }

        return $this->hasAccess($user, $approvalLevel, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_shelf_approval::level');
    }

    public function forceDelete(User $user, ApprovalLevel $approvalLevel): bool
    {
        if (! $user->can('force_delete_shelf_approval::level')) {
            return false;
        }

        return $this->hasAccess($user, $approvalLevel, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_shelf_approval::level');
    }

    public function restore(User $user, ApprovalLevel $approvalLevel): bool
    {
        if (! $user->can('restore_shelf_approval::level')) {
            return false;
        }

        return $this->hasAccess($user, $approvalLevel, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_shelf_approval::level');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_shelf_approval::level');
    }
}
