<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\TransferDivision;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TransferDivisionPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_division');
    }

    public function view(User $user, TransferDivision $division): bool
    {
        return $user->can('view_form_transfer_division');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_division');
    }

    public function update(User $user, TransferDivision $division): bool
    {
        if (! $user->can('update_form_transfer_division')) {
            return false;
        }

        return $this->hasAccess($user, $division);
    }

    public function delete(User $user, TransferDivision $division): bool
    {
        if (! $user->can('delete_form_transfer_division')) {
            return false;
        }

        return $this->hasAccess($user, $division);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_division');
    }

    public function forceDelete(User $user, TransferDivision $division): bool
    {
        if (! $user->can('force_delete_form_transfer_division')) {
            return false;
        }

        return $this->hasAccess($user, $division);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_division');
    }

    public function restore(User $user, TransferDivision $division): bool
    {
        if (! $user->can('restore_form_transfer_division')) {
            return false;
        }

        return $this->hasAccess($user, $division);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_division');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_form_transfer_division');
    }
}
