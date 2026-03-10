<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class JobApplicationHistory extends Model
{
    use HasFactory;

    protected $table = 'rekrutmen_job_application_histories';

    protected $fillable = [
        'job_application_id',
        'from_stage_id',
        'to_stage_id',
        'status',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'status' => JobApplicationStatus::class,
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id')->withTrashed();
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'from_stage_id')->withTrashed();
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'to_stage_id')->withTrashed();
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by')->withTrashed();
    }
}
