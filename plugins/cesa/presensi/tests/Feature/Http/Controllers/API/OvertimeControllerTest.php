<?php

namespace Cesa\Presensi\Tests\Feature\Http\Controllers\API;

use App\Models\User;
use Cesa\Presensi\Models\Office;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Models\Shift;
use Cesa\Presensi\Tests\PresensiTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class OvertimeControllerTest extends PresensiTestCase
{
    public function test_store_rejects_invalid_attachment_types(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

        $this->post('/admin/api/v1/presensi/overtimes', [
            'date'       => now()->toDateString(),
            'start_time' => '18:00',
            'end_time'   => '20:00',
            'reason'     => 'Deployment support',
            'file'       => UploadedFile::fake()->create('evidence.exe', 50, 'application/octet-stream'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('presensi_overtimes', 0);
    }

    public function test_store_accepts_supported_attachment_types(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

        $this->post('/admin/api/v1/presensi/overtimes', [
            'date'       => now()->toDateString(),
            'start_time' => '18:00',
            'end_time'   => '20:00',
            'reason'     => 'Production support',
            'file'       => UploadedFile::fake()->create('evidence.pdf', 120, 'application/pdf'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $overtime = Overtime::query()->sole();

        $this->assertNotNull($overtime->attachment);
        Storage::disk('public')->assertExists($overtime->attachment);
    }

    public function test_index_returns_latest_overtimes_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $olderOvertime = Overtime::query()->create([
            'user_id'    => $user->id,
            'date'       => now()->subDays(5)->toDateString(),
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'status'     => 'approved',
            'reason'     => 'Older overtime',
        ]);
        $olderOvertime->forceFill(['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)])->save();

        $latestOvertime = Overtime::query()->create([
            'user_id'    => $user->id,
            'date'       => now()->subDay()->toDateString(),
            'start_time' => '18:00:00',
            'end_time'   => '20:00:00',
            'status'     => 'approved',
            'reason'     => 'Latest overtime',
        ]);
        $latestOvertime->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

        $this->getJson('/admin/api/v1/presensi/overtimes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latestOvertime->id)
            ->assertJsonPath('data.1.id', $olderOvertime->id);
    }

    public function test_store_accepts_legacy_attendance_rows_that_use_created_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

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
            'start_time'          => '08:00:00',
            'end_time'            => '19:00:00',
            'is_leave'            => false,
            'created_at'          => now()->subDay()->setTime(8, 0),
            'updated_at'          => now()->subDay()->setTime(19, 0),
        ]);

        $attendanceDate = now()->subDay()->toDateString();

        $this->post('/admin/api/v1/presensi/overtimes', [
            'date'       => $attendanceDate,
            'start_time' => '18:00',
            'end_time'   => '18:30',
            'reason'     => 'Legacy attendance compatibility',
        ], [
            'Accept' => 'application/json',
        ])
            ->assertCreated()
            ->assertJsonPath('data.reason', 'Legacy attendance compatibility');
    }

    public function test_store_rejects_past_request_without_attendance(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createScheduleFor($user);

        $this->post('/admin/api/v1/presensi/overtimes', [
            'date'       => now()->subDay()->toDateString(),
            'start_time' => '18:00',
            'end_time'   => '19:00',
            'reason'     => 'Past overtime without attendance',
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.date.0', 'Attendance record not found for the requested date.');
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
