<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Webkul\Security\Traits\HasPermissionScope;

abstract class ShelfModel extends Model
{
    use HasPermissionScope, SoftDeletes;

    public function __construct(array $attributes = [])
    {
        $this->mergeFillable([
            'creator_id',
        ]);

        $this->mergeCasts([
            'creator_id' => 'integer',
        ]);

        parent::__construct($attributes);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->usesCreatorColumn()) {
                return;
            }

            $model->creator_id ??= Auth::id();
        });
    }

    protected function belongsToIncludingTrashed(
        string $related,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
        ?string $relation = null,
    ): BelongsTo {
        if ($relation === null) {
            $relation = $this->guessBelongsToRelation();
        }

        return $this->belongsTo($related, $foreignKey, $ownerKey, $relation)->withTrashed();
    }

    protected function hasManyIncludingTrashed(
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasMany {
        return $this->hasMany($related, $foreignKey, $localKey)->withTrashed();
    }

    protected function hasOneIncludingTrashed(
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne {
        return $this->hasOne($related, $foreignKey, $localKey)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class, 'creator_id');
    }

    public function getTable(): string
    {
        $table = parent::getTable();

        return Str::startsWith($table, 'shelf_')
            ? $table
            : 'shelf_'.$table;
    }

    /** @var array<string, bool> */
    protected static array $creatorColumnCache = [];

    protected function usesCreatorColumn(): bool
    {
        $class = static::class;

        if (array_key_exists($class, static::$creatorColumnCache)) {
            return static::$creatorColumnCache[$class];
        }

        $schema = $this->getConnection()->getSchemaBuilder();

        return static::$creatorColumnCache[$class] = $schema->hasTable($this->getTable())
            && $schema->hasColumn($this->getTable(), 'creator_id');
    }
}
