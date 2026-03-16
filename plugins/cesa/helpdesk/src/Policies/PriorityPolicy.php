<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\Priority;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class PriorityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_priority');
    }

    public function view(User $user, Priority $priority): bool
    {
        return $user->can('view_helpdesk_priority');
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_priority');
    }

    public function update(User $user, Priority $priority): bool
    {
        return $user->can('update_helpdesk_priority');
    }

    public function delete(User $user, Priority $priority): bool
    {
        return $user->can('delete_helpdesk_priority');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_priority');
    }

    public function forceDelete(User $user, Priority $priority): bool
    {
        return $user->can('force_delete_helpdesk_priority');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_priority');
    }

    public function restore(User $user, Priority $priority): bool
    {
        return $user->can('restore_helpdesk_priority');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_priority');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_helpdesk_priority');
    }
}
