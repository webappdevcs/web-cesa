<?php

namespace Cesa\Presensi\Tests\Feature\Http\Controllers\API;

use App\Models\User;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Office;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Models\Shift;
use Cesa\Presensi\Tests\PresensiTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class AuthAndAttendanceControllerTest extends PresensiTestCase
{
    protected function tearDown(): void
    {
        RateLimiter::clear('login|127.0.0.1');
        RateLimiter::clear('127.0.0.1');

        parent::tearDown();
    }

    public function test_login_is_throttled_after_multiple_failed_attempts(): void
    {
        User::factory()->create([
            'email'    => 'presensi@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/presensi/login', [
                'email'    => 'presensi@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/presensi/login', [
            'email'    => 'presensi@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_store_attendance_keeps_end_time_null_until_checkout(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

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

        $this->post('/api/store-attendance', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('checkin.jpg'),
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $attendance = Attendance::query()->sole();

        $this->assertNotNull($attendance->start_time);
        $this->assertNull($attendance->end_time);

        $this->post('/api/store-attendance', [
            'latitude'  => -6.2,
            'longitude' => 106.8,
            'photo'     => UploadedFile::fake()->image('checkout.jpg'),
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $attendance->refresh();

        $this->assertNotNull($attendance->end_time);
        $this->assertNotNull($attendance->end_photo_path);
    }

    public function test_get_attendance_by_month_and_year_returns_latest_records_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $latestAttendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.200000,
            'schedule_longitude'  => 106.816666,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.200100,
            'start_longitude'     => 106.816700,
            'end_latitude'        => -6.200100,
            'end_longitude'       => 106.816700,
            'start_time'          => '08:05:00',
            'end_time'            => '17:00:00',
            'is_leave'            => false,
        ]);
        $latestAttendance->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

        $olderAttendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => -6.200000,
            'schedule_longitude'  => 106.816666,
            'schedule_start_time' => '08:00:00',
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.200100,
            'start_longitude'     => 106.816700,
            'end_latitude'        => -6.200100,
            'end_longitude'       => 106.816700,
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'is_leave'            => false,
        ]);
        $olderAttendance->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();

        $response = $this->getJson('/api/get-attendance-by-month-year/'.now()->month.'/'.now()->year);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.date', $latestAttendance->created_at->toDateString())
            ->assertJsonPath('data.1.date', $olderAttendance->created_at->toDateString());
    }
}
