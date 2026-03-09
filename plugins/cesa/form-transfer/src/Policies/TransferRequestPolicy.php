<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\TransferRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TransferRequestPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_transfer::request');
    }

    public function view(User $user, TransferRequest $transferRequest): bool
    {
        return $user->can('view_form_transfer_transfer::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_transfer::request');
    }

    public function update(User $user, TransferRequest $transferRequest): bool
    {
        if (! $user->can('update_form_transfer_transfer::request')) {
            return false;
        }

        return $this->hasAccess($user, $transferRequest);
    }

    public function delete(User $user, TransferRequest $transferRequest): bool
    {
        if (! $user->can('delete_form_transfer_transfer::request')) {
            return false;
        }

        return $this->hasAccess($user, $transferRequest);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_transfer::request');
    }

    public function forceDelete(User $user, TransferRequest $transferRequest): bool
    {
        if (! $user->can('force_delete_form_transfer_transfer::request')) {
            return false;
        }

        return $this->hasAccess($user, $transferRequest);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_transfer::request');
    }

    public function restore(User $user, TransferRequest $transferRequest): bool
    {
        if (! $user->can('restore_form_transfer_transfer::request')) {
            return false;
        }

        return $this->hasAccess($user, $transferRequest);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_transfer::request');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_form_transfer_transfer::request');
    }
}
