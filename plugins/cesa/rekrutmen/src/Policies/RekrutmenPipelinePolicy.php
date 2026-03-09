<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class RekrutmenPipelinePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rekrutmen_rekrutmen::pipeline');
    }

    public function view(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('view_rekrutmen_rekrutmen::pipeline');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rekrutmen_rekrutmen::pipeline');
    }

    public function update(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('update_rekrutmen_rekrutmen::pipeline');
    }

    public function delete(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('delete_rekrutmen_rekrutmen::pipeline');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rekrutmen_rekrutmen::pipeline');
    }

    public function forceDelete(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('force_delete_rekrutmen_rekrutmen::pipeline');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rekrutmen_rekrutmen::pipeline');
    }

    public function restore(User $user, RekrutmenPipeline $rekrutmenPipeline): bool
    {
        return $user->can('restore_rekrutmen_rekrutmen::pipeline');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rekrutmen_rekrutmen::pipeline');
    }
}
