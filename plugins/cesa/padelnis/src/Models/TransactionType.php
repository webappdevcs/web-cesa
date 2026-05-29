<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\TransactionTypeFactory;
use Cesa\Padelnis\Enums\TransactionType as TransactionTypeCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Webkul\Security\Traits\HasNullableCreator;

class TransactionType extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'padelnis_transaction_types';

    protected $fillable = [
        'code',
        'name',
        'requires_coach',
        'requires_catalog_item',
        'is_active',
        'sort',
    ];

    protected static function booted(): void
    {
        static::creating(function (TransactionType $transactionType): void {
            if ((int) ($transactionType->sort ?? 0) <= 0) {
                $transactionType->sort = static::nextSortValue();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_coach'        => 'boolean',
            'requires_catalog_item' => 'boolean',
            'is_active'             => 'boolean',
            'sort'                  => 'integer',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
            'deleted_at'            => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }

    public static function labelFor(mixed $code): string
    {
        $normalizedCode = static::normalizeCode($code);

        try {
            $label = static::query()
                ->active()
                ->where('code', $normalizedCode)
                ->value('name');
        } catch (\Throwable) {
            $label = null;
        }

        if ($label) {
            return $label;
        }

        $fallbackType = TransactionTypeCode::tryFrom($normalizedCode);

        return $fallbackType?->label() ?? Str::headline(str_replace(['-', '_'], ' ', $normalizedCode));
    }

    public static function requiresCoach(mixed $code): bool
    {
        return static::requirementFor($code, 'requires_coach')
            ?? (TransactionTypeCode::tryFrom(static::normalizeCode($code))?->requiresCoach() ?? false);
    }

    public static function requiresCatalogItem(mixed $code): bool
    {
        return static::requirementFor($code, 'requires_catalog_item')
            ?? (TransactionTypeCode::tryFrom(static::normalizeCode($code))?->requiresCatalogItem() ?? false);
    }

    public function setCodeAttribute(mixed $value): void
    {
        $this->attributes['code'] = static::normalizeCode($value);
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['name'] = Reservation::normalizeDisplayName($value);
    }

    public function setSortAttribute(mixed $value): void
    {
        $this->attributes['sort'] = blank($value) ? 0 : max(0, (int) $value);
    }

    protected static function requirementFor(mixed $code, string $column): ?bool
    {
        $normalizedCode = static::normalizeCode($code);

        try {
            $value = static::query()
                ->active()
                ->where('code', $normalizedCode)
                ->value($column);
        } catch (\Throwable) {
            return null;
        }

        return $value === null ? null : (bool) $value;
    }

    public static function normalizeCode(mixed $code): string
    {
        if ($code instanceof TransactionTypeCode) {
            return $code->value;
        }

        $normalizedCode = Str::slug((string) $code, '_');

        return $normalizedCode === '' ? TransactionTypeCode::Regular->value : mb_substr($normalizedCode, 0, 20);
    }

    protected static function newFactory(): TransactionTypeFactory
    {
        return TransactionTypeFactory::new();
    }

    protected static function nextSortValue(): int
    {
        return ((int) static::withTrashed()->max('sort')) + 1;
    }
}
