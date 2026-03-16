<?php

namespace Cesa\Helpdesk\Traits;

use Illuminate\Support\Facades\Auth;

trait HasHelpdeskResourceAccess
{
    public static function userCan(string $permission, mixed $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return (bool) $user->can($permission);
    }
}
