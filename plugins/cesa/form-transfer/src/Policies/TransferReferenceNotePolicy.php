<?php

namespace Cesa\FormTransfer\Policies;

use Cesa\FormTransfer\Models\TransferReferenceNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TransferReferenceNotePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form_transfer_reference::note');
    }

    public function view(User $user, TransferReferenceNote $note): bool
    {
        return $user->can('view_form_transfer_reference::note');
    }

    public function create(User $user): bool
    {
        return $user->can('create_form_transfer_reference::note');
    }

    public function update(User $user, TransferReferenceNote $note): bool
    {
        if (! $user->can('update_form_transfer_reference::note')) {
            return false;
        }

        return $this->hasAccess($user, $note);
    }

    public function delete(User $user, TransferReferenceNote $note): bool
    {
        if (! $user->can('delete_form_transfer_reference::note')) {
            return false;
        }

        return $this->hasAccess($user, $note);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form_transfer_reference::note');
    }

    public function forceDelete(User $user, TransferReferenceNote $note): bool
    {
        if (! $user->can('force_delete_form_transfer_reference::note')) {
            return false;
        }

        return $this->hasAccess($user, $note);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form_transfer_reference::note');
    }

    public function restore(User $user, TransferReferenceNote $note): bool
    {
        if (! $user->can('restore_form_transfer_reference::note')) {
            return false;
        }

        return $this->hasAccess($user, $note);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form_transfer_reference::note');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_form_transfer_reference::note');
    }
}
