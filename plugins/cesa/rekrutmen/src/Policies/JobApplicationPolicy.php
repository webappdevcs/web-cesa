<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\JobApplication;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class JobApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rekrutmen_job::application');
    }

    public function view(User $user, JobApplication $jobApplication): bool
    {
        return $user->can('view_rekrutmen_job::application');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rekrutmen_job::application');
    }

    public function update(User $user, JobApplication $jobApplication): bool
    {
        return $user->can('update_rekrutmen_job::application');
    }

    public function delete(User $user, JobApplication $jobApplication): bool
    {
        return $user->can('delete_rekrutmen_job::application');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rekrutmen_job::application');
    }

    public function forceDelete(User $user, JobApplication $jobApplication): bool
    {
        return $user->can('force_delete_rekrutmen_job::application');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rekrutmen_job::application');
    }

    public function restore(User $user, JobApplication $jobApplication): bool
    {
        return $user->can('restore_rekrutmen_job::application');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rekrutmen_job::application');
    }
}
