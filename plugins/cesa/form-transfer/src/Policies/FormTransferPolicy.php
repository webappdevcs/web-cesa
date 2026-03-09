<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\FormTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class FormTransferPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_form::transfer');
    }

    public function view(User $user, FormTransfer $formTransfer): bool
    {
        return $user->can('view_form_transfer_form::transfer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_form::transfer');
    }

    public function update(User $user, FormTransfer $formTransfer): bool
    {
        if (! $user->can('update_form_transfer_form::transfer')) {
            return false;
        }

        return $this->hasAccess($user, $formTransfer);
    }

    public function delete(User $user, FormTransfer $formTransfer): bool
    {
        if (! $user->can('delete_form_transfer_form::transfer')) {
            return false;
        }

        return $this->hasAccess($user, $formTransfer);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_form::transfer');
    }

    public function forceDelete(User $user, FormTransfer $formTransfer): bool
    {
        if (! $user->can('force_delete_form_transfer_form::transfer')) {
            return false;
        }

        return $this->hasAccess($user, $formTransfer);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_form::transfer');
    }

    public function restore(User $user, FormTransfer $formTransfer): bool
    {
        if (! $user->can('restore_form_transfer_form::transfer')) {
            return false;
        }

        return $this->hasAccess($user, $formTransfer);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_form::transfer');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_form_transfer_form::transfer');
    }
}
