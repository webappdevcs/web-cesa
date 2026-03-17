<?php

namespace Cesa\Shelf\Policies;

use Cesa\Shelf\Models\CompanyDocumentSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasScopedPermissions;

class CompanyDocumentSettingPolicy
{
    use HandlesAuthorization, HasScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shelf_company::document::setting');
    }

    public function view(User $user, CompanyDocumentSetting $companyDocumentSetting): bool
    {
        if (! $user->can('view_shelf_company::document::setting')) {
            return false;
        }

        return $this->hasAccess($user, $companyDocumentSetting, 'creator');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shelf_company::document::setting');
    }

    public function update(User $user, CompanyDocumentSetting $companyDocumentSetting): bool
    {
        if (! $user->can('update_shelf_company::document::setting')) {
            return false;
        }

        return $this->hasAccess($user, $companyDocumentSetting, 'creator');
    }

    public function delete(User $user, CompanyDocumentSetting $companyDocumentSetting): bool
    {
        if (! $user->can('delete_shelf_company::document::setting')) {
            return false;
        }

        return $this->hasAccess($user, $companyDocumentSetting, 'creator');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_shelf_company::document::setting');
    }

    public function forceDelete(User $user, CompanyDocumentSetting $companyDocumentSetting): bool
    {
        if (! $user->can('force_delete_shelf_company::document::setting')) {
            return false;
        }

        return $this->hasAccess($user, $companyDocumentSetting, 'creator');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_shelf_company::document::setting');
    }

    public function restore(User $user, CompanyDocumentSetting $companyDocumentSetting): bool
    {
        if (! $user->can('restore_shelf_company::document::setting')) {
            return false;
        }

        return $this->hasAccess($user, $companyDocumentSetting, 'creator');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_shelf_company::document::setting');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_shelf_company::document::setting');
    }
}
