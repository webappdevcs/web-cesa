<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\Coach;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class CoachPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_coach');
    }

    public function view(User $user, Coach $coach): bool
    {
        return $user->can('view_padelnis_coach')
            && $this->hasAccess($user, $coach, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_coach');
    }

    public function update(User $user, Coach $coach): bool
    {
        return $user->can('update_padelnis_coach')
            && $this->hasAccess($user, $coach, 'creator');
    }

    public function delete(User $user, Coach $coach): bool
    {
        return $user->can('delete_padelnis_coach')
            && $this->hasAccess($user, $coach, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_coach');
    }

    public function forceDelete(User $user, Coach $coach): bool
    {
        return $user->can('force_delete_padelnis_coach')
            && $this->hasAccess($user, $coach, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_coach');
    }

    public function restore(User $user, Coach $coach): bool
    {
        return $user->can('restore_padelnis_coach')
            && $this->hasAccess($user, $coach, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_coach');
    }
}
