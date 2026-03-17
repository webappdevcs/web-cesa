<?php

namespace Cesa\Shelf\Policies;

use Cesa\Shelf\Enums\RequestStatus;
use Cesa\Shelf\Models\AssetRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class AssetRequestPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'view_any');
    }

    public function view(User $user, AssetRequest $assetRequest): bool
    {
        return $this->canAccess($user, 'view')
            && $this->hasAccess($user, $assetRequest, 'resourceUsers');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, 'create');
    }

    public function update(User $user, AssetRequest $assetRequest): bool
    {
        return $this->canAccess($user, 'update')
            && $this->hasAccess($user, $assetRequest, 'resourceUsers');
    }

    public function delete(User $user, AssetRequest $assetRequest): bool
    {
        return $this->canAccess($user, 'delete')
            && $this->hasAccess($user, $assetRequest, 'resourceUsers')
            && $this->canMutateRecord($assetRequest);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccess($user, 'delete_any');
    }

    public function forceDelete(User $user, AssetRequest $assetRequest): bool
    {
        return $this->canAccess($user, 'force_delete')
            && $this->hasAccess($user, $assetRequest, 'resourceUsers')
            && $this->canMutateRecord($assetRequest);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->canAccess($user, 'force_delete_any');
    }

    public function restore(User $user, AssetRequest $assetRequest): bool
    {
        return $this->canAccess($user, 'restore')
            && $this->hasAccess($user, $assetRequest, 'resourceUsers')
            && $this->canMutateRecord($assetRequest);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccess($user, 'restore_any');
    }

    public function reorder(User $user): bool
    {
        return $this->canAccess($user, 'reorder');
    }

    private function canMutateRecord(AssetRequest $assetRequest): bool
    {
        return $assetRequest->status === RequestStatus::Pending;
    }

    private function canAccess(User $user, string $ability): bool
    {
        return $user->can("{$ability}_shelf_asset::request")
            || $user->can("{$ability}_shelf_public::asset::request");
    }
}
