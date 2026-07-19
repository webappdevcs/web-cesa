<?php

namespace Cesa\Kepegawaian\Policies;

use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Security\Models\User;

class HrWorkflowTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kepegawaian_hr::workflow::template');
    }

    public function view(User $user, HrWorkflowTemplate $template): bool
    {
        return $user->can('view_kepegawaian_hr::workflow::template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_kepegawaian_hr::workflow::template');
    }

    public function update(User $user, HrWorkflowTemplate $template): bool
    {
        return $user->can('update_kepegawaian_hr::workflow::template');
    }

    public function delete(User $user, HrWorkflowTemplate $template): bool
    {
        return $user->can('delete_kepegawaian_hr::workflow::template');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_kepegawaian_hr::workflow::template');
    }

    public function restore(User $user, HrWorkflowTemplate $template): bool
    {
        return $user->can('restore_kepegawaian_hr::workflow::template');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_kepegawaian_hr::workflow::template');
    }

    public function forceDelete(User $user, HrWorkflowTemplate $template): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
