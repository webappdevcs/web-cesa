<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Cesa\Shelf\Enums\ApprovalStatus;
use Cesa\Shelf\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetRequest extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    public const REQUEST_TYPES = [
        'pengadaan_aset' => 'Pengadaan Aset',
        'perbaikan_aset' => 'Perbaikan Aset',
        'penarikan_aset' => 'Penarikan Aset',
    ];

    protected $fillable = [
        'uuid',
        'request_type',
        'requester_name',
        'email',
        'division',
        'approval_track',
        'placement',
        'item_name',
        'qty',
        'attachment_path',
        'attachment_original_name',
        'status',
        'admin_notes',
        'user_id',
        'asset_id',
    ];

    protected $casts = [
        'uuid'   => 'string',
        'status' => RequestStatus::class,
    ];

    protected function managedFileAttributes(): array
    {
        return [
            'attachment_path' => [
                'directory'               => 'shelf/asset-requests/attachments',
                'original_name_attribute' => 'attachment_original_name',
            ],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Asset::class);
    }

    public function getResourceUsersAttribute(): EloquentCollection
    {
        $users = new EloquentCollection;

        foreach ([$this->creator, $this->user] as $relatedUser) {
            if ($relatedUser && ! $users->contains('id', $relatedUser->id)) {
                $users->push($relatedUser);
            }
        }

        return $users;
    }

    public function approvals(): HasMany
    {
        return $this->hasManyIncludingTrashed(RequestApproval::class)->orderBy('level');
    }

    /**
     * Get the current (active) approval step.
     */
    public function currentApproval(): ?RequestApproval
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('level')
            ->first();
    }

    /**
     * Calculate the overall approval status based on all approval steps.
     */
    public function computeOverallStatus(): RequestStatus
    {
        $approvals = $this->approvals;

        if ($approvals->isEmpty()) {
            return $this->status;
        }

        // If any is rejected → rejected
        if ($approvals->contains('status', ApprovalStatus::Rejected)) {
            return RequestStatus::Rejected;
        }

        // If all approved → approved
        if ($approvals->every(fn ($a) => $a->status === ApprovalStatus::Approved)) {
            return RequestStatus::Approved;
        }

        // Otherwise still pending
        return RequestStatus::Pending;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->managedFileUrl('attachment_path');
    }

    public function getAttachmentLabelAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return $this->attachment_original_name ?: basename($this->attachment_path);
    }

    public static function requestTypeOptions(): array
    {
        return self::REQUEST_TYPES;
    }

    public static function getRequestTypeLabel(string $value): string
    {
        return self::REQUEST_TYPES[$value] ?? $value;
    }
}
