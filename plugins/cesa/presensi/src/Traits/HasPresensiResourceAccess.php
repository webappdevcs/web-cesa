<?php

namespace Cesa\Presensi\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasPresensiResourceAccess
{
    public static function userCan(string $permission, mixed $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return (bool) $user->can($permission);
    }

    public static function applyAuthenticatedUserScope(Builder $query): Builder
    {
        return $query;
    }
}
