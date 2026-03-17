<?php

namespace Cesa\Shelf\Policies;

use Cesa\Shelf\Models\Asset;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class AssetPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shelf_asset');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        if (! $user->can('view_shelf_asset')) {
            return false;
        }

        return $this->hasAccess($user, $asset, 'resourceUsers');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_shelf_asset');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        if (! $user->can('update_shelf_asset')) {
            return false;
        }

        return $this->hasAccess($user, $asset, 'resourceUsers');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): bool
    {
        if (! $user->can('delete_shelf_asset')) {
            return false;
        }

        return $this->hasAccess($user, $asset, 'resourceUsers');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_shelf_asset');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        if (! $user->can('force_delete_shelf_asset')) {
            return false;
        }

        return $this->hasAccess($user, $asset, 'resourceUsers');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_shelf_asset');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Asset $asset): bool
    {
        if (! $user->can('restore_shelf_asset')) {
            return false;
        }

        return $this->hasAccess($user, $asset, 'resourceUsers');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_shelf_asset');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_shelf_asset');
    }
}
