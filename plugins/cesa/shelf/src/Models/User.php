<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User as BaseUser;
use Webkul\Support\Models\Company;

class User extends BaseUser
{
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_default', false);
    }

    public static function applySelectableScope(Builder $query, ?int $exceptUserId = null): Builder
    {
        return $query
            ->selectable()
            ->when($exceptUserId !== null, fn (Builder $builder): Builder => $builder->whereKeyNot($exceptUserId));
    }

    public static function selectableQuery(?int $exceptUserId = null): Builder
    {
        return static::applySelectableScope(static::query(), $exceptUserId);
    }

    /**
     * @return array<int, string>
     */
    public static function selectableOptions(?int $exceptUserId = null): array
    {
        return static::selectableQuery($exceptUserId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'default_company_id')->withTrashed();
    }

    public static function supportsJobTitles(): bool
    {
        return Schema::hasTable('employees_employees')
            && Schema::hasTable('employees_job_positions');
    }

    public function jobTitle(): HasOneThrough
    {
        return $this->hasOneThrough(
            JobTitle::class,
            Employee::class,
            'user_id',
            'id',
            'id',
            'job_id',
        )->withTrashedParents()->withTrashed();
    }

    public function assetTransfersFrom(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'from_user_id')->withTrashed();
    }

    public function assetTransfersTo(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'to_user_id')->withTrashed();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id')->withTrashed();
    }
}
