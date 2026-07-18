<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Database\Factories\EmployeeIdentifierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeIdentifier extends Model
{
    use HasFactory;

    protected $table = 'employees_employee_identifiers';

    protected $fillable = [
        'employee_id',
        'source_system',
        'source_instance',
        'identifier_type',
        'external_id',
        'metadata',
        'verified_at',
        'last_seen_at',
        'retired_at',
        'creator_id',
    ];

    protected $casts = [
        'metadata'     => 'encrypted:array',
        'verified_at'  => 'datetime',
        'last_seen_at' => 'datetime',
        'retired_at'   => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('retired_at');
    }

    public function retire(): void
    {
        if ($this->retired_at !== null) {
            return;
        }

        $this->forceFill(['retired_at' => now()])->save();
    }

    protected static function booted(): void
    {
        static::creating(function (self $identifier): void {
            $identifier->creator_id ??= Auth::id();
        });

        static::saving(function (self $identifier): void {
            $identifier->source_system = self::normalizeKey($identifier->source_system);
            $identifier->source_instance = self::normalizeKey($identifier->source_instance);
            $identifier->identifier_type = self::normalizeKey($identifier->identifier_type);
            $identifier->external_id = Str::squish($identifier->external_id);
            $identifier->normalized_value = self::normalizeKey($identifier->external_id);
        });

        static::updating(function (self $identifier): void {
            if ($identifier->isDirty([
                'employee_id',
                'source_system',
                'source_instance',
                'identifier_type',
                'external_id',
                'normalized_value',
            ])) {
                throw new LogicException(
                    'External identity fields are immutable. Retire this identifier and create a new one.'
                );
            }
        });
    }

    protected static function newFactory(): EmployeeIdentifierFactory
    {
        return EmployeeIdentifierFactory::new();
    }

    private static function normalizeKey(string $value): string
    {
        return Str::lower(Str::squish($value));
    }
}
