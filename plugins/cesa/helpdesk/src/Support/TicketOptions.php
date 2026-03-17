<?php

namespace Cesa\Helpdesk\Support;

use Cesa\Helpdesk\Models\Unit;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class TicketOptions
{
    /**
     * @return array<int, int>
     */
    public static function companyIdsForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->can('view_any_helpdesk_ticket')) {
            return Company::query()
                ->orderBy('name')
                ->pluck('id')
                ->map(fn (mixed $value): int => (int) $value)
                ->all();
        }

        return array_values(array_filter(array_unique(array_merge(
            $user->allowedCompanies()
                ->pluck('companies.id')
                ->map(fn (mixed $value): int => (int) $value)
                ->all(),
            $user->default_company_id ? [(int) $user->default_company_id] : [],
        ))));
    }

    public static function companyOptionsForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $companyIds = static::companyIdsForUser($user);

        if ($companyIds === []) {
            return [];
        }

        return Company::query()
            ->whereIn('id', $companyIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function defaultCompanyIdForUser(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        if ($user->default_company_id) {
            return (int) $user->default_company_id;
        }

        return array_key_first(static::companyOptionsForUser($user));
    }

    public static function unitUserOptions(mixed $unitId): array
    {
        if (! $unitId) {
            return [];
        }

        return Unit::query()
            ->find($unitId)?->users()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'users.id')
            ->all() ?? [];
    }
}
