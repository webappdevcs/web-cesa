<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\Court;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class CourtPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_court');
    }

    public function view(User $user, Court $court): bool
    {
        return $user->can('view_padelnis_court')
            && $this->hasAccess($user, $court, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_court');
    }

    public function update(User $user, Court $court): bool
    {
        return $user->can('update_padelnis_court')
            && $this->hasAccess($user, $court, 'creator');
    }

    public function delete(User $user, Court $court): bool
    {
        return $user->can('delete_padelnis_court')
            && $this->hasAccess($user, $court, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_court');
    }

    public function forceDelete(User $user, Court $court): bool
    {
        return $user->can('force_delete_padelnis_court')
            && $this->hasAccess($user, $court, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_court');
    }

    public function restore(User $user, Court $court): bool
    {
        return $user->can('restore_padelnis_court')
            && $this->hasAccess($user, $court, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_court');
    }
}
