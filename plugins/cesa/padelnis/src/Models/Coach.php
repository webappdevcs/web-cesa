<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\CoachFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;

class Coach extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'padelnis_coaches';

    protected $fillable = [
        'name',
        'phone',
        'is_active',
        'sort',
    ];

    protected static function booted(): void
    {
        static::creating(function (Coach $coach): void {
            if ((int) ($coach->sort ?? 0) <= 0) {
                $coach->sort = static::nextSortValue();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort'       => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function specialPrices(): HasMany
    {
        return $this->hasMany(SpecialPrice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['name'] = Reservation::normalizeDisplayName($value);
    }

    public function setPhoneAttribute(mixed $value): void
    {
        $this->attributes['phone'] = blank($value) ? null : trim((string) $value);
    }

    public function setSortAttribute(mixed $value): void
    {
        $this->attributes['sort'] = blank($value) ? 0 : max(0, (int) $value);
    }

    protected static function newFactory(): CoachFactory
    {
        return CoachFactory::new();
    }

    protected static function nextSortValue(): int
    {
        return ((int) static::withTrashed()->max('sort')) + 1;
    }
}
