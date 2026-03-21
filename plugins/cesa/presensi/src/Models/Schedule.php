<?php

namespace Cesa\Presensi\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Webkul\Security\Models\User;

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

    public static function resolveActiveForUser(int $userId, CarbonInterface|string|null $date = null): ?self
    {
        return static::query()
            ->with(['office', 'shift'])
            ->where('user_id', $userId)
            ->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class)->withTrashed();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class)->withTrashed();
    }

    public function scheduleStartAt(CarbonInterface $attendanceDate): ?Carbon
    {
        if (! $this->shift) {
            return null;
        }

        return Carbon::parse($attendanceDate->toDateString().' '.$this->shift->start_time);
    }

    public function scheduleEndAt(CarbonInterface $attendanceDate): ?Carbon
    {
        if (! $this->shift) {
            return null;
        }

        $scheduleStartAt = $this->scheduleStartAt($attendanceDate);
        $scheduleEndAt = Carbon::parse($attendanceDate->toDateString().' '.$this->shift->end_time);

        if ($scheduleStartAt instanceof Carbon && $scheduleEndAt->lessThanOrEqualTo($scheduleStartAt)) {
            $scheduleEndAt->addDay();
        }

        return $scheduleEndAt;
    }

    public function checkInOpensAt(CarbonInterface $attendanceDate): ?Carbon
    {
        return null;
    }

    public function earlyCheckOutThresholdAt(CarbonInterface $attendanceDate): ?Carbon
    {
        return null;
    }
}
