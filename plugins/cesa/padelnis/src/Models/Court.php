<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;

class Court extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'padelnis_courts';

    protected $fillable = [
        'name',
        'court_type',
        'is_active',
        'sort',
    ];

    protected static function booted(): void
    {
        static::creating(function (Court $court): void {
            if ((int) ($court->sort ?? 0) <= 0) {
                $court->sort = static::nextSortValue();
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
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function idOptions(?string $courtType = null): array
    {
        return static::query()
            ->active()
            ->when(filled($courtType), fn (Builder $query): Builder => $query->where('court_type', $courtType))
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function courtTypeOptions(): array
    {
        return static::query()
            ->active()
            ->whereNotNull('court_type')
            ->orderBy('court_type')
            ->pluck('court_type', 'court_type')
            ->unique()
            ->all();
    }

    public function setNameAttribute(mixed $value): void
    {
        $name = Reservation::normalizeDisplayName($value);

        $this->attributes['name'] = preg_replace('/\bVip\b/u', 'VIP', $name) ?: $name;
    }

    public function setCourtTypeAttribute(mixed $value): void
    {
        $this->attributes['court_type'] = static::normalizeCourtType($value);
    }

    public function setSortAttribute(mixed $value): void
    {
        $this->attributes['sort'] = blank($value) ? 0 : max(0, (int) $value);
    }

    public static function normalizeCourtType(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim((string) $value));

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    protected static function newFactory(): CourtFactory
    {
        return CourtFactory::new();
    }

    protected static function nextSortValue(): int
    {
        return ((int) static::withTrashed()->max('sort')) + 1;
    }
}
