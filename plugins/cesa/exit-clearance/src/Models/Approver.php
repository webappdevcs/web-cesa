<?php

namespace Cesa\ExitClearance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approver extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exit_clearance_approvers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'title',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Security\Models\User::class, 'created_by')->withTrashed();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'exit_clearance_department_approver', 'approver_id', 'department_id')
            ->withTrashed();
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(Request::class, 'exit_clearance_request_approver', 'approver_id', 'request_id')
            ->withPivot(['approved_at', 'notes', 'status'])
            ->withTimestamps()
            ->withTrashed();
    }
}
