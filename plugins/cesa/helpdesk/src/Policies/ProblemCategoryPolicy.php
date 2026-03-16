<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\ProblemCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class ProblemCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_problem::category');
    }

    public function view(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('view_helpdesk_problem::category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_problem::category');
    }

    public function update(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('update_helpdesk_problem::category');
    }

    public function delete(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('delete_helpdesk_problem::category');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_problem::category');
    }

    public function forceDelete(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('force_delete_helpdesk_problem::category');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_problem::category');
    }

    public function restore(User $user, ProblemCategory $problemCategory): bool
    {
        return $user->can('restore_helpdesk_problem::category');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_problem::category');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_helpdesk_problem::category');
    }
}
