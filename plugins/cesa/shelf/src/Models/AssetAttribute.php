<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAttribute extends ShelfModel
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'custom_attribute_id',
        'attribute_value',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Asset::class);
    }

    public function customAttribute(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(CustomAssetAttribute::class, 'custom_attribute_id');
    }
}
