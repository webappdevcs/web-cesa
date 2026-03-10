<?php

namespace Cesa\Presensi\Tests\Unit\Models;

use App\Models\User;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Office;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Models\Shift;
use Cesa\Presensi\Tests\PresensiTestCase;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Webkul\Security\Models\User as SecurityUser;

class AttendanceModelTest extends PresensiTestCase
{
    public function test_attendance_detects_late_checkin_and_formats_work_duration(): void
    {
        $attendance = new Attendance([
            'schedule_start_time' => '08:00:00',
            'start_time'          => '08:15:00',
            'end_time'            => '17:45:00',
        ]);

        $this->assertTrue($attendance->isLate());
        $this->assertSame('9 jam 30 menit', $attendance->workDuration());
    }

    public function test_schedule_relations_and_boolean_casts_are_consistent(): void
    {
        $user = User::factory()->create();
        $office = Office::query()->create([
            'name'      => 'HQ',
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'radius'    => 150,
        ]);
        $shift = Shift::query()->create([
            'name'       => 'Shift A',
            'start_time' => '08:00:00',
            'end_time'   => '17:00:00',
        ]);

        $schedule = Schedule::query()->create([
            'user_id'   => $user->id,
            'shift_id'  => $shift->id,
            'office_id' => $office->id,
            'is_wfa'    => 1,
            'is_banned' => 0,
        ]);

        $this->assertTrue($schedule->is_wfa);
        $this->assertFalse($schedule->is_banned);
        $this->assertSame($user->id, $schedule->user->id);
        $this->assertSame($office->id, $schedule->office->id);
        $this->assertSame($shift->id, $schedule->shift->id);
    }

    public function test_overtime_casts_date_attribute_to_carbon(): void
    {
        $user = User::factory()->create();

        $overtime = Overtime::query()->create([
            'user_id'    => $user->id,
            'date'       => '2026-03-03',
            'start_time' => '18:00:00',
            'end_time'   => '21:00:00',
            'reason'     => 'Deployment support',
            'status'     => 'approved',
        ]);

        $this->assertInstanceOf(Carbon::class, $overtime->date);
        $this->assertSame('2026-03-03', $overtime->date->toDateString());
    }

    public function test_presensi_models_use_soft_deletes_when_schema_supports_it(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Attendance::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Office::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Shift::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(\Cesa\Presensi\Models\Leave::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Overtime::class));
    }

    public function test_soft_deleted_relations_remain_readable(): void
    {
        $user = User::factory()->create();
        $office = Office::query()->create([
            'name'      => 'HQ',
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'radius'    => 150,
        ]);
        $shift = Shift::query()->create([
            'name'       => 'Shift A',
            'start_time' => '08:00:00',
            'end_time'   => '17:00:00',
        ]);

        $schedule = Schedule::query()->create([
            'user_id'   => $user->id,
            'shift_id'  => $shift->id,
            'office_id' => $office->id,
            'is_wfa'    => false,
            'is_banned' => false,
        ]);

        $leave = Leave::query()->create([
            'user_id'    => $user->id,
            'type'       => 'Izin',
            'start_date' => '2026-03-01',
            'end_date'   => '2026-03-01',
            'reason'     => 'Personal',
            'status'     => 'pending',
        ]);

        $overtime = Overtime::query()->create([
            'user_id'    => $user->id,
            'date'       => '2026-03-03',
            'start_time' => '18:00:00',
            'end_time'   => '21:00:00',
            'reason'     => 'Deployment support',
            'status'     => 'approved',
        ]);

        $attendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.200000,
            'schedule_longitude'  => 106.816666,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.200100,
            'start_longitude'     => 106.816700,
            'start_time'          => '08:05:00',
            'end_time'            => '17:00:00',
        ]);

        $office->delete();
        $shift->delete();
        $leave->delete();
        $overtime->delete();
        SecurityUser::query()->findOrFail($user->id)->delete();

        $freshSchedule = Schedule::query()->findOrFail($schedule->id);
        $freshAttendance = Attendance::query()->findOrFail($attendance->id);
        $freshUser = SecurityUser::withTrashed()->findOrFail($user->id);

        $this->assertSame($office->id, $freshSchedule->office?->id);
        $this->assertSame($shift->id, $freshSchedule->shift?->id);
        $this->assertSame($user->id, $freshAttendance->user?->id);
        $this->assertTrue($freshUser->leaves->contains('id', $leave->id));
        $this->assertTrue($freshUser->overtimes->contains('id', $overtime->id));
    }
}
