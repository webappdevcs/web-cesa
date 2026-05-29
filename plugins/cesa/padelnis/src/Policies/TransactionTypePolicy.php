<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\TransactionType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class TransactionTypePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_transaction::type');
    }

    public function view(User $user, TransactionType $transactionType): bool
    {
        return $user->can('view_padelnis_transaction::type')
            && $this->hasAccess($user, $transactionType, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_transaction::type');
    }

    public function update(User $user, TransactionType $transactionType): bool
    {
        return $user->can('update_padelnis_transaction::type')
            && $this->hasAccess($user, $transactionType, 'creator');
    }

    public function delete(User $user, TransactionType $transactionType): bool
    {
        return $user->can('delete_padelnis_transaction::type')
            && $this->hasAccess($user, $transactionType, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_transaction::type');
    }

    public function forceDelete(User $user, TransactionType $transactionType): bool
    {
        return $user->can('force_delete_padelnis_transaction::type')
            && $this->hasAccess($user, $transactionType, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_transaction::type');
    }

    public function restore(User $user, TransactionType $transactionType): bool
    {
        return $user->can('restore_padelnis_transaction::type')
            && $this->hasAccess($user, $transactionType, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_transaction::type');
    }
}
