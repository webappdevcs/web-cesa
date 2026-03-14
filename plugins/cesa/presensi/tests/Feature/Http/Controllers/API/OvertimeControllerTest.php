<?php

namespace Cesa\Presensi\Tests\Feature\Http\Controllers\API;

use App\Models\User;
use Cesa\Presensi\Models\Office;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Models\Shift;
use Cesa\Presensi\Tests\PresensiTestCase;
use Illuminate\Http\UploadedFile;
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

        $response = $this->post('/api/overtimes', [
            'date'       => now()->toDateString(),
            'start_time' => '18:00',
            'end_time'   => '20:00',
            'reason'     => 'Deployment support',
            'file'       => UploadedFile::fake()->create('evidence.exe', 50, 'application/octet-stream'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
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

        $response = $this->post('/api/overtimes', [
            'date'       => now()->toDateString(),
            'start_time' => '18:00',
            'end_time'   => '20:00',
            'reason'     => 'Production support',
            'file'       => UploadedFile::fake()->create('evidence.pdf', 120, 'application/pdf'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();

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

        $response = $this->getJson('/api/overtimes');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $latestOvertime->id)
            ->assertJsonPath('data.1.id', $olderOvertime->id);
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
