<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\EmployeeCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class EmployeeCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_employee::category');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmployeeCategory $employeeCategory): bool
    {
        return $user->can('view_kepegawaian_employee::category');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_kepegawaian_employee::category');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmployeeCategory $employeeCategory): bool
    {
        return $user->can('update_kepegawaian_employee::category');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmployeeCategory $employeeCategory): bool
    {
        return $user->can('delete_kepegawaian_employee::category');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_kepegawaian_employee::category');
    }
}
