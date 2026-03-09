<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\RequestManPower;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class RequestManPowerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rekrutmen_request::man::power');
    }

    public function view(User $user, RequestManPower $requestManPower): bool
    {
        return $user->can('view_rekrutmen_request::man::power');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rekrutmen_request::man::power');
    }

    public function update(User $user, RequestManPower $requestManPower): bool
    {
        return $user->can('update_rekrutmen_request::man::power');
    }

    public function delete(User $user, RequestManPower $requestManPower): bool
    {
        return $user->can('delete_rekrutmen_request::man::power');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rekrutmen_request::man::power');
    }

    public function forceDelete(User $user, RequestManPower $requestManPower): bool
    {
        return $user->can('force_delete_rekrutmen_request::man::power');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rekrutmen_request::man::power');
    }

    public function restore(User $user, RequestManPower $requestManPower): bool
    {
        return $user->can('restore_rekrutmen_request::man::power');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rekrutmen_request::man::power');
    }
}
