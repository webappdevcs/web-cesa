<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

class HrWorkflowRun extends Model
{
    protected $table = 'employees_hr_workflow_runs';

    protected $fillable = [
        'template_id',
        'employee_id',
        'template_code',
        'name',
        'type',
        'status',
        'active_key',
        'source_key',
        'context',
        'started_at',
        'due_at',
        'completed_at',
        'cancelled_at',
        'started_by',
        'completed_by',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'type'         => HrWorkflowType::class,
        'status'       => HrWorkflowRunStatus::class,
        'context'      => 'array',
        'started_at'   => 'datetime',
        'due_at'       => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowTemplate::class, 'template_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HrWorkflowTask::class, 'run_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            $run->uuid ??= (string) Str::orderedUuid();
        });
    }
}
