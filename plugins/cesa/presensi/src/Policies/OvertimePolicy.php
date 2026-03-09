<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Overtime;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class OvertimePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_overtime');
    }

    public function view(User $user, Overtime $overtime): bool
    {
        return $user->can('view_presensi_overtime');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_overtime');
    }

    public function update(User $user, Overtime $overtime): bool
    {
        if (! $user->can('update_presensi_overtime')) {
            return false;
        }

        return $this->hasAccess($user, $overtime);
    }

    public function delete(User $user, Overtime $overtime): bool
    {
        if (! $user->can('delete_presensi_overtime')) {
            return false;
        }

        return $this->hasAccess($user, $overtime);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_overtime');
    }
}
