<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekrutmen_job_postings';

    protected $fillable = [
        'request_man_power_id',
        'rekrutmen_pipeline_id',
        'title',
        'slug',
        'description',
        'requirements',
        'location',
        'is_published',
        'closing_date',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'closing_date' => 'date',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function requestManPower(): BelongsTo
    {
        return $this->belongsTo(RequestManPower::class, 'request_man_power_id')->withTrashed();
    }

    public function rekrutmenPipeline(): BelongsTo
    {
        return $this->belongsTo(RekrutmenPipeline::class, 'rekrutmen_pipeline_id')->withTrashed();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_posting_id');
    }
}
