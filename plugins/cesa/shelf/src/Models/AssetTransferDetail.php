<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransferDetail extends ShelfModel
{
    use HasFactory;

    protected $fillable = [
        'asset_transfer_id',
        'asset_id',
        'equipment',
    ];

    public function assetTransfer(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(AssetTransfer::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Asset::class);
    }
}
