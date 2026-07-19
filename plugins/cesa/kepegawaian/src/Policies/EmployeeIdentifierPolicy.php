<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class EmployeeIdentifierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_employee');
    }

    public function view(User $user, EmployeeIdentifier $identifier): bool
    {
        return $user->can('view_kepegawaian_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_identifiers_kepegawaian_employee');
    }

    public function update(User $user, EmployeeIdentifier $identifier): bool
    {
        return $user->can('manage_identifiers_kepegawaian_employee');
    }

    public function delete(User $user, EmployeeIdentifier $identifier): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
