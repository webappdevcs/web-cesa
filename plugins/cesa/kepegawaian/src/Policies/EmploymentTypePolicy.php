<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\EmploymentType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class EmploymentTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_employment::type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('view_kepegawaian_employment::type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_kepegawaian_employment::type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('update_kepegawaian_employment::type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('delete_kepegawaian_employment::type');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_kepegawaian_employment::type');
    }
}
