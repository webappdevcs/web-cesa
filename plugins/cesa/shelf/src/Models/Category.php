<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends ShelfModel
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasManyIncludingTrashed(Category::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasManyIncludingTrashed(Asset::class);
    }
}
