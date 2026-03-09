<?php

namespace Cesa\Rekrutmen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekrutmenStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekrutmen_stages';

    protected $fillable = [
        'rekrutmen_pipeline_id',
        'name',
        'order_column',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(RekrutmenPipeline::class, 'rekrutmen_pipeline_id')->withTrashed();
    }
}
