<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\CatalogItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class CatalogItemPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_catalog::item');
    }

    public function view(User $user, CatalogItem $catalogItem): bool
    {
        return $user->can('view_padelnis_catalog::item')
            && $this->hasAccess($user, $catalogItem, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_catalog::item');
    }

    public function update(User $user, CatalogItem $catalogItem): bool
    {
        return $user->can('update_padelnis_catalog::item')
            && $this->hasAccess($user, $catalogItem, 'creator');
    }

    public function delete(User $user, CatalogItem $catalogItem): bool
    {
        return $user->can('delete_padelnis_catalog::item')
            && $this->hasAccess($user, $catalogItem, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_catalog::item');
    }

    public function forceDelete(User $user, CatalogItem $catalogItem): bool
    {
        return $user->can('force_delete_padelnis_catalog::item')
            && $this->hasAccess($user, $catalogItem, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_catalog::item');
    }

    public function restore(User $user, CatalogItem $catalogItem): bool
    {
        return $user->can('restore_padelnis_catalog::item')
            && $this->hasAccess($user, $catalogItem, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_catalog::item');
    }
}
