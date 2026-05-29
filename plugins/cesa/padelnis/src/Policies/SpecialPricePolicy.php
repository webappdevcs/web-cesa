<?php

namespace Cesa\Padelnis\Policies;

use Cesa\Padelnis\Models\SpecialPrice;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class SpecialPricePolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_padelnis_special::price');
    }

    public function view(User $user, SpecialPrice $specialPrice): bool
    {
        return $user->can('view_padelnis_special::price')
            && $this->hasAccess($user, $specialPrice, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_padelnis_special::price');
    }

    public function update(User $user, SpecialPrice $specialPrice): bool
    {
        return $user->can('update_padelnis_special::price')
            && $this->hasAccess($user, $specialPrice, 'creator');
    }

    public function delete(User $user, SpecialPrice $specialPrice): bool
    {
        return $user->can('delete_padelnis_special::price')
            && $this->hasAccess($user, $specialPrice, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_padelnis_special::price');
    }

    public function forceDelete(User $user, SpecialPrice $specialPrice): bool
    {
        return $user->can('force_delete_padelnis_special::price')
            && $this->hasAccess($user, $specialPrice, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_padelnis_special::price');
    }

    public function restore(User $user, SpecialPrice $specialPrice): bool
    {
        return $user->can('restore_padelnis_special::price')
            && $this->hasAccess($user, $specialPrice, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_padelnis_special::price');
    }
}
