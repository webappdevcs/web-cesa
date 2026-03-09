<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\TransferBank;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TransferBankPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_bank');
    }

    public function view(User $user, TransferBank $bank): bool
    {
        return $user->can('view_form_transfer_bank');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_bank');
    }

    public function update(User $user, TransferBank $bank): bool
    {
        if (! $user->can('update_form_transfer_bank')) {
            return false;
        }

        return $this->hasAccess($user, $bank);
    }

    public function delete(User $user, TransferBank $bank): bool
    {
        if (! $user->can('delete_form_transfer_bank')) {
            return false;
        }

        return $this->hasAccess($user, $bank);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_bank');
    }

    public function forceDelete(User $user, TransferBank $bank): bool
    {
        if (! $user->can('force_delete_form_transfer_bank')) {
            return false;
        }

        return $this->hasAccess($user, $bank);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_bank');
    }

    public function restore(User $user, TransferBank $bank): bool
    {
        if (! $user->can('restore_form_transfer_bank')) {
            return false;
        }

        return $this->hasAccess($user, $bank);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_bank');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_form_transfer_bank');
    }
}
