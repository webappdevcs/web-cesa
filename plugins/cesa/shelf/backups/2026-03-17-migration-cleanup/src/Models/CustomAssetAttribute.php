<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomAssetAttribute extends ShelfModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'required',
        'is_active',
        'category_id',
        'is_notifiable',
        'notification_type',
        'notification_offset',
        'fixed_notification_date',
    ];

    protected $casts = [
        'category_id' => 'array',
    ];

    public function setCategoryIdAttribute(array $value): void
    {
        $this->attributes['category_id'] = json_encode(array_map('intval', $value));
    }

    public function category(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Category::class);
    }

    public function assetAttributes(): HasMany
    {
        return $this->hasManyIncludingTrashed(AssetAttribute::class, 'custom_attribute_id');
    }
}
