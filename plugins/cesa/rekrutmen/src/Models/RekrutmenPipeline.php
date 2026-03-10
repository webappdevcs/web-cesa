<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekrutmenPipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekrutmen_pipelines';

    protected $fillable = [
        'name',
        'description',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(RekrutmenStage::class, 'rekrutmen_pipeline_id')->withTrashed()->orderBy('order_column');
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'rekrutmen_pipeline_id')->withTrashed();
    }
}
