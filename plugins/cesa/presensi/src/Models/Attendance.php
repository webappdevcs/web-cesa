<?php

namespace Cesa\Presensi\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Webkul\Security\Models\User;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    public const CHECK_IN_STATUS_ON_TIME = 'on_time';

    public const CHECK_IN_STATUS_LATE = 'late';

    public const CHECK_OUT_STATUS_ON_TIME = 'on_time';

    public const CHECK_OUT_STATUS_EARLY_LEAVE = 'early_leave';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ON_LEAVE = 'on_leave';

    protected $table = 'presensi_attendances';

    protected $fillable = [
        'user_id',
        'schedule_latitude',
        'schedule_longitude',
        'schedule_start_time',
        'schedule_end_time',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'start_time',
        'end_time',
        'is_leave',
        'start_photo_path',
        'end_photo_path',
    ];

    protected $casts = [
        'is_leave' => 'boolean',
    ];

    public function scopeForAttendanceDate(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('created_at', Carbon::parse($date)->toDateString());
    }

    public function scopeForAttendanceMonth(Builder $query, int $month, int $year): Builder
    {
        return $query
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);
    }

    public function scopeOrderByAttendanceDate(Builder $query, string $direction = 'desc'): Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class)->withTrashed();
    }

    public function isLate(): bool
    {
        return $this->resolvedCheckInStatus() === self::CHECK_IN_STATUS_LATE;
    }

    public function isEarlyLeave(): bool
    {
        return $this->resolvedCheckOutStatus() === self::CHECK_OUT_STATUS_EARLY_LEAVE;
    }

    public static function resolveAttendanceStatus(bool $hasCheckedOut, bool $isLeave = false): string
    {
        if ($isLeave) {
            return self::STATUS_ON_LEAVE;
        }

        return $hasCheckedOut ? self::STATUS_CLOSED : self::STATUS_OPEN;
    }

    public function checkedInAt(): ?Carbon
    {
        if (blank($this->start_time)) {
            return null;
        }

        $attendanceDate = $this->attendanceDate()?->toDateString();

        return Carbon::parse(($attendanceDate ? $attendanceDate.' ' : '').$this->start_time);
    }

    public function checkedOutAt(): ?Carbon
    {
        if (blank($this->end_time)) {
            return null;
        }

        $attendanceDate = $this->attendanceDate()?->toDateString();
        $checkedOutAt = Carbon::parse(($attendanceDate ? $attendanceDate.' ' : '').$this->end_time);
        $checkedInAt = $this->checkedInAt();

        if ($checkedInAt instanceof Carbon && $checkedOutAt->lessThanOrEqualTo($checkedInAt)) {
            $checkedOutAt->addDay();
        }

        return $checkedOutAt;
    }

    public function scheduleStartAt(): ?Carbon
    {
        if (blank($this->schedule_start_time)) {
            return null;
        }

        $attendanceDate = $this->attendanceDate()?->toDateString();

        return Carbon::parse(($attendanceDate ? $attendanceDate.' ' : '').$this->schedule_start_time);
    }

    public function scheduleEndAt(): ?Carbon
    {
        if (blank($this->schedule_end_time)) {
            return null;
        }

        $attendanceDate = $this->attendanceDate()?->toDateString();
        $scheduleEndAt = Carbon::parse(($attendanceDate ? $attendanceDate.' ' : '').$this->schedule_end_time);
        $scheduleStartAt = $this->scheduleStartAt();

        if ($scheduleStartAt instanceof Carbon && $scheduleEndAt->lessThanOrEqualTo($scheduleStartAt)) {
            $scheduleEndAt->addDay();
        }

        return $scheduleEndAt;
    }

    public function workDuration(): string
    {
        $checkedInAt = $this->checkedInAt();
        $checkedOutAt = $this->checkedOutAt();

        if (! $checkedInAt instanceof Carbon || ! $checkedOutAt instanceof Carbon) {
            return '-';
        }

        $duration = $checkedInAt->diff($checkedOutAt);

        return "{$duration->h} jam {$duration->i} menit";
    }

    public function attendanceDate(): ?Carbon
    {
        if ($this->created_at instanceof Carbon) {
            return $this->created_at->copy()->startOfDay();
        }

        if (filled($this->getAttribute('created_at'))) {
            return Carbon::parse((string) $this->getAttribute('created_at'))->startOfDay();
        }

        return null;
    }

    public function resolvedCheckInStatus(): ?string
    {
        $scheduleStartAt = $this->scheduleStartAt();
        $checkedInAt = $this->checkedInAt();

        if (! $scheduleStartAt instanceof Carbon || ! $checkedInAt instanceof Carbon || $this->is_leave) {
            return null;
        }

        return $checkedInAt->greaterThan($scheduleStartAt)
            ? self::CHECK_IN_STATUS_LATE
            : self::CHECK_IN_STATUS_ON_TIME;
    }

    public function resolvedCheckOutStatus(): ?string
    {
        $scheduleEndAt = $this->scheduleEndAt();
        $checkedOutAt = $this->checkedOutAt();

        if (! $scheduleEndAt instanceof Carbon || ! $checkedOutAt instanceof Carbon || $this->is_leave) {
            return null;
        }

        return $checkedOutAt->lessThan($scheduleEndAt)
            ? self::CHECK_OUT_STATUS_EARLY_LEAVE
            : self::CHECK_OUT_STATUS_ON_TIME;
    }

    public function resolvedAttendanceStatus(): string
    {
        return self::resolveAttendanceStatus(filled($this->end_time), (bool) $this->is_leave);
    }

    /**
     * @return array<int, string>
     */
    public function resolvedAttendanceFlags(): array
    {
        $flags = [];

        if ($this->resolvedCheckInStatus() === self::CHECK_IN_STATUS_LATE) {
            $flags[] = self::CHECK_IN_STATUS_LATE;
        }

        if ($this->resolvedCheckOutStatus() === self::CHECK_OUT_STATUS_EARLY_LEAVE) {
            $flags[] = self::CHECK_OUT_STATUS_EARLY_LEAVE;
        }

        return $flags;
    }

    public function resolvedAttendanceFlagLabel(): string
    {
        $flags = $this->resolvedAttendanceFlags();

        if ($flags === []) {
            return 'None';
        }

        return collect($flags)
            ->map(fn (string $flag): string => str($flag)->replace('_', ' ')->title()->toString())
            ->implode(', ');
    }
}
