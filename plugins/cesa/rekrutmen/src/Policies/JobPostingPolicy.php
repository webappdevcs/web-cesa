<?php

namespace Cesa\Rekrutmen\Policies;

use Cesa\Rekrutmen\Models\JobPosting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class JobPostingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rekrutmen_job::posting');
    }

    public function view(User $user, JobPosting $jobPosting): bool
    {
        return $user->can('view_rekrutmen_job::posting');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rekrutmen_job::posting');
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $user->can('update_rekrutmen_job::posting');
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $user->can('delete_rekrutmen_job::posting');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rekrutmen_job::posting');
    }

    public function forceDelete(User $user, JobPosting $jobPosting): bool
    {
        return $user->can('force_delete_rekrutmen_job::posting');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rekrutmen_job::posting');
    }

    public function restore(User $user, JobPosting $jobPosting): bool
    {
        return $user->can('restore_rekrutmen_job::posting');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rekrutmen_job::posting');
    }
}
