<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\WorkLocation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class WorkLocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkLocation $workLocation): bool
    {
        return $user->can('view_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkLocation $workLocation): bool
    {
        return $user->can('update_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkLocation $workLocation): bool
    {
        return $user->can('delete_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, WorkLocation $workLocation): bool
    {
        return $user->can('force_delete_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, WorkLocation $workLocation): bool
    {
        return $user->can('restore_kepegawaian_work::location');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_kepegawaian_work::location');
    }
}
