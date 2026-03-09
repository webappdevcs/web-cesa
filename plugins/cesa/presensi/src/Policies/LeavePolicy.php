<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Leave;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class LeavePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_leave');
    }

    public function view(User $user, Leave $leave): bool
    {
        return $user->can('view_presensi_leave');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_leave');
    }

    public function update(User $user, Leave $leave): bool
    {
        if (! $user->can('update_presensi_leave')) {
            return false;
        }

        return $this->hasAccess($user, $leave);
    }

    public function delete(User $user, Leave $leave): bool
    {
        if (! $user->can('delete_presensi_leave')) {
            return false;
        }

        return $this->hasAccess($user, $leave);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_leave');
    }
}
