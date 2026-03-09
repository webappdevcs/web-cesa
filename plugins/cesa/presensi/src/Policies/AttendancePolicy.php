<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Attendance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class AttendancePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_attendance');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('view_presensi_attendance');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_attendance');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if (! $user->can('update_presensi_attendance')) {
            return false;
        }

        return $this->hasAccess($user, $attendance);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        if (! $user->can('delete_presensi_attendance')) {
            return false;
        }

        return $this->hasAccess($user, $attendance);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_attendance');
    }
}
