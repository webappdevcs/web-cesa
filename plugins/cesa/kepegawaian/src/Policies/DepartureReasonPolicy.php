<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\DepartureReason;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class DepartureReasonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_departure::reason');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DepartureReason $departureReason): bool
    {
        return $user->can('view_kepegawaian_departure::reason');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_kepegawaian_departure::reason');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DepartureReason $departureReason): bool
    {
        return $user->can('update_kepegawaian_departure::reason');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DepartureReason $departureReason): bool
    {
        return $user->can('delete_kepegawaian_departure::reason');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_kepegawaian_departure::reason');
    }
}
