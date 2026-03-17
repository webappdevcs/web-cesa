<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends ShelfModel
{
    protected $fillable = ['name'];

    public function assets(): HasMany
    {
        return $this->hasManyIncludingTrashed(Asset::class);
    }
}
