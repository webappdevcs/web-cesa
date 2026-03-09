<?php

namespace Cesa\Presensi\Policies;

use Cesa\Presensi\Models\Schedule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class SchedulePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_presensi_schedule');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->can('view_presensi_schedule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_presensi_schedule');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if (! $user->can('update_presensi_schedule')) {
            return false;
        }

        return $this->hasAccess($user, $schedule);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if (! $user->can('delete_presensi_schedule')) {
            return false;
        }

        return $this->hasAccess($user, $schedule);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_presensi_schedule');
    }
}
