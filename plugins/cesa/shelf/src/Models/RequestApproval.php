<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestApproval extends ShelfModel
{
    use HasFactory;

    protected $fillable = [
        'asset_request_id',
        'approval_level_id',
        'token',
        'level',
        'approver_name',
        'approver_email',
        'status',
        'notes',
        'responded_at',
    ];

    protected $casts = [
        'status'       => ApprovalStatus::class,
        'responded_at' => 'datetime',
    ];

    public function assetRequest(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(AssetRequest::class);
    }

    public function approvalLevel(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(ApprovalLevel::class);
    }
}
