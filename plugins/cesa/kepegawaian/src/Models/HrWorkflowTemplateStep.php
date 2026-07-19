<?php

namespace Cesa\Kepegawaian\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class HrWorkflowTemplateStep extends Model
{
    protected $table = 'employees_hr_workflow_template_steps';

    protected $fillable = [
        'sort_order',
        'name',
        'department',
        'due_days',
        'is_required',
        'requires_evidence',
        'default_assignee_id',
    ];

    protected $casts = [
        'sort_order'       => 'integer',
        'due_days'         => 'integer',
        'is_required'      => 'boolean',
        'requires_evidence'=> 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrWorkflowTemplate::class, 'template_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }
}
