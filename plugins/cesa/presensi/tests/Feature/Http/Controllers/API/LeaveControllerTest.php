<?php

namespace Cesa\Presensi\Tests\Feature\Http\Controllers\API;

use App\Models\User;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Tests\PresensiTestCase;
use Laravel\Sanctum\Sanctum;

class LeaveControllerTest extends PresensiTestCase
{
    public function test_index_returns_latest_leaves_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $olderLeave = Leave::query()->create([
            'user_id'    => $user->id,
            'type'       => 'Izin',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date'   => now()->subDays(4)->toDateString(),
            'reason'     => 'Older leave',
            'status'     => 'approved',
        ]);
        $olderLeave->forceFill(['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)])->save();

        $latestLeave = Leave::query()->create([
            'user_id'    => $user->id,
            'type'       => 'Cuti',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->toDateString(),
            'reason'     => 'Latest leave',
            'status'     => 'pending',
        ]);
        $latestLeave->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

        $response = $this->getJson('/admin/api/v1/presensi/leaves');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $latestLeave->id)
            ->assertJsonPath('data.1.id', $olderLeave->id);
    }
}
