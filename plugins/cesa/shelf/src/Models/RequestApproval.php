<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Employee\Models\Employee;

class RequestApproval extends ShelfModel
{
    use HasFactory;

    protected $fillable = [
        'asset_request_id',
        'approval_level_id',
        'token',
        'level',
        'approver_employee_id',
        'approver_user_id',
        'approver_name',
        'approver_email',
        'status',
        'notes',
        'responded_at',
        'notified_at',
    ];

    protected $casts = [
        'status'       => ApprovalStatus::class,
        'responded_at' => 'datetime',
        'notified_at'  => 'datetime',
    ];

    public function assetRequest(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(AssetRequest::class);
    }

    public function approvalLevel(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(ApprovalLevel::class);
    }

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function hasActiveApprover(): bool
    {
        $employee = $this->approverEmployee()->first();

        if ($employee === null || ! $employee->user_id) {
            return false;
        }

        $user = $this->approverUser()->first();

        return $user !== null && $user->is_active;
    }
}
