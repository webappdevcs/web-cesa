<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class HrWorkflowTask extends Model
{
    protected $table = 'employees_hr_workflow_tasks';

    protected $fillable = [
        'template_step_id',
        'sort_order',
        'name',
        'department',
        'status',
        'is_required',
        'requires_evidence',
        'assigned_to_id',
        'due_at',
        'completed_at',
        'completed_by',
        'completion_note',
        'evidence_path',
    ];

    protected $casts = [
        'sort_order'        => 'integer',
        'status'            => HrWorkflowTaskStatus::class,
        'is_required'       => 'boolean',
        'requires_evidence' => 'boolean',
        'due_at'            => 'datetime',
        'completed_at'      => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowRun::class, 'run_id');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowTemplateStep::class, 'template_step_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
