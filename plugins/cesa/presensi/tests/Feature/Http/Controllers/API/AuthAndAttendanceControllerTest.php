<?php

namespace Cesa\Presensi\Tests\Feature\Http\Controllers\API;

use App\Models\User;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Office;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Models\Shift;
use Cesa\Presensi\Tests\PresensiTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class AuthAndAttendanceControllerTest extends PresensiTestCase
{
    public function test_check_in_and_check_out_keep_legacy_attendance_schema_compatible(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Carbon::setTestNow('2026-03-19 07:55:00');

        $this->createScheduleFor($user);

        $this->post('/admin/api/v1/presensi/attendance/check-in', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('checkin.jpg'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertCreated()
            ->assertJsonPath('data.attendance_status', 'open')
            ->assertJsonPath('data.check_in_status', 'on_time');

        $attendance = Attendance::query()->sole();

        $this->assertNotNull($attendance->start_time);
        $this->assertNull($attendance->end_time);
        $this->assertNull($attendance->getAttribute('schedule_id'));

        Carbon::setTestNow('2026-03-19 17:05:00');

        $this->post('/admin/api/v1/presensi/attendance/check-out', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('checkout.jpg'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertOk()
            ->assertJsonPath('data.attendance_status', 'closed')
            ->assertJsonPath('data.check_out_status', 'on_time');

        $attendance->refresh();

        $this->assertNotNull($attendance->end_time);
        $this->assertNotNull($attendance->end_photo_path);

        Carbon::setTestNow();
    }

    public function test_check_in_rejects_when_user_has_already_checked_in(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Carbon::setTestNow('2026-03-19 08:05:00');

        $this->createScheduleFor($user);

        $attendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.2,
            'start_longitude'     => 106.8,
            'start_time'          => '08:01:00',
            'is_leave'            => false,
        ]);
        $attendance->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        $this->post('/admin/api/v1/presensi/attendance/check-in', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('duplicate-checkin.jpg'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Anda sudah melakukan check in hari ini.');

        Carbon::setTestNow();
    }

    public function test_check_out_rejects_active_attendance_from_previous_day(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

        $attendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.2,
            'start_longitude'     => 106.8,
            'start_time'          => '08:01:00',
            'is_leave'            => false,
        ]);
        $attendance->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        $this->post('/admin/api/v1/presensi/attendance/check-out', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('yesterday-checkout.jpg'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Presensi terbuka dari hari sebelumnya harus diselesaikan secara manual oleh admin.');
    }

    public function test_get_attendance_by_month_and_year_returns_latest_records_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $latestAttendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.2,
            'start_longitude'     => 106.8,
            'end_latitude'        => -6.2,
            'end_longitude'       => 106.8,
            'start_time'          => '08:05:00',
            'end_time'            => '17:00:00',
            'is_leave'            => false,
        ]);
        $latestAttendance->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

        $olderAttendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.2,
            'start_longitude'     => 106.8,
            'end_latitude'        => -6.2,
            'end_longitude'       => 106.8,
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'is_leave'            => false,
        ]);
        $olderAttendance->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();

        $this->getJson('/admin/api/v1/presensi/attendance/history/'.now()->month.'/'.now()->year)
            ->assertOk()
            ->assertJsonPath('data.0.date', $latestAttendance->created_at->toDateString())
            ->assertJsonPath('data.1.date', $olderAttendance->created_at->toDateString());
    }

    public function test_schedule_ban_requires_schedule_update_permission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

        $this->postJson('/admin/api/v1/presensi/schedule/ban')
            ->assertForbidden();
    }

    public function test_get_attendance_today_reads_legacy_rows_that_only_use_created_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        DB::table('presensi_attendances')->insert([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.2,
            'start_longitude'     => 106.8,
            'end_latitude'        => -6.2,
            'end_longitude'       => 106.8,
            'start_time'          => '08:10:00',
            'end_time'            => '17:05:00',
            'is_leave'            => false,
            'created_at'          => now()->setTime(8, 10),
            'updated_at'          => now()->setTime(17, 5),
        ]);

        $this->getJson('/admin/api/v1/presensi/attendance/today')
            ->assertOk()
            ->assertJsonPath('data.today.date', now()->toDateString())
            ->assertJsonPath('data.today.check_in_status', 'late')
            ->assertJsonPath('data.today.attendance_status', 'closed')
            ->assertJsonPath('data.today.attendance_flags.0', 'late');
    }

    private function createScheduleFor(User $user): void
    {
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

        Schedule::query()->create([
            'user_id'   => $user->id,
            'shift_id'  => $shift->id,
            'office_id' => $office->id,
            'is_wfa'    => false,
            'is_banned' => false,
        ]);
    }
}
