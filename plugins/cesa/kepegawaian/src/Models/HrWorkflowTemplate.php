<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class HrWorkflowTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'employees_hr_workflow_templates';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'type'      => HrWorkflowType::class,
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(HrWorkflowTemplateStep::class, 'template_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(HrWorkflowRun::class, 'template_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            $template->uuid ??= (string) Str::orderedUuid();
        });
    }
}
