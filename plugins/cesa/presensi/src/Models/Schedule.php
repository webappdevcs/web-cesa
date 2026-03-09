<?php

namespace Cesa\Presensi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'presensi_schedules';

    protected $casts = [
        'is_wfa'    => 'boolean',
        'is_banned' => 'boolean',
    ];

    protected $fillable = [
        'user_id',
        'shift_id',
        'office_id',
        'is_wfa',
        'is_banned',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class)->withTrashed();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class)->withTrashed();
    }
}
