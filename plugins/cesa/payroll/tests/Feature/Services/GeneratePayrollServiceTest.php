<?php

namespace Cesa\Payroll\Tests\Feature\Services;

use App\Models\User;
use Cesa\Payroll\Models\PayrollPeriod;
use Cesa\Payroll\Models\PayrollRecord;
use Cesa\Payroll\Services\GeneratePayrollService;
use Cesa\Payroll\Settings\PayrollSettings;
use Cesa\Payroll\Tests\PayrollTestCase;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Overtime;

class GeneratePayrollServiceTest extends PayrollTestCase
{
    private PayrollSettings $settings;

    private GeneratePayrollService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(PayrollSettings::class);
        $this->settings = $reflection->newInstanceWithoutConstructor();
        $this->settings->daily_wage = 120000;
        $this->settings->overtime_hourly_rate = 15000;
        $this->settings->late_penalty_tier_1_min = 6;
        $this->settings->late_penalty_tier_1_amount = 20000;
        $this->settings->late_penalty_tier_2_min = 16;
        $this->settings->late_penalty_tier_2_amount = 50000;
        $this->settings->late_penalty_tier_3_percent = 50;

        $this->service = new GeneratePayrollService($this->settings);
    }

    public function test_generate_creates_payroll_record_and_locks_period(): void
    {
        $period = PayrollPeriod::query()->create([
            'name'       => 'March 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'open',
        ]);

        $user = User::factory()->create();

        $this->createAttendance($user->id, '08:00:00', '08:10:00', '17:00:00');
        $this->createAttendance($user->id, '08:00:00', '08:00:00', '17:00:00');
        $this->createOvertime($user->id, now()->toDateString(), '18:00:00', '20:00:00', 'approved');

        $this->service->generate($period->fresh());

        $record = PayrollRecord::query()
            ->where('user_id', $user->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(2, $record->total_attendance_days);
        $this->assertSame(10, $record->total_late_minutes);
        $this->assertSame('2.00', $record->total_overtime_hours);
        $this->assertSame('270000.00', $record->gross_salary);
        $this->assertSame('20000.00', $record->total_penalties);
        $this->assertSame('250000.00', $record->net_salary);
        $this->assertSame('locked', $period->fresh()->status);
    }

    public function test_generate_replaces_existing_records_when_regenerated(): void
    {
        $period = PayrollPeriod::query()->create([
            'name'       => 'March 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'open',
        ]);

        $user = User::factory()->create();

        $existingRecord = PayrollRecord::query()->create([
            'user_id'               => $user->id,
            'payroll_period_id'     => $period->id,
            'total_attendance_days' => 1,
            'gross_salary'          => 100000,
            'total_penalties'       => 0,
            'net_salary'            => 100000,
        ]);

        $this->createAttendance($user->id, '08:00:00', '08:00:00', '17:00:00');

        $this->service->generate($period->fresh());

        $records = PayrollRecord::query()
            ->where('user_id', $user->id)
            ->where('payroll_period_id', $period->id)
            ->get();

        $this->assertCount(1, $records);
        $this->assertNotSame($existingRecord->id, $records->first()->id);
    }

    public function test_generate_only_creates_records_for_users_with_payroll_data(): void
    {
        $period = PayrollPeriod::query()->create([
            'name'       => 'March 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'open',
        ]);

        $attendanceUser = User::factory()->create();
        $overtimeUser = User::factory()->create();
        $inactiveUser = User::factory()->create();

        $this->createAttendance($attendanceUser->id, '08:00:00', '08:05:00', '17:00:00');
        $this->createOvertime($overtimeUser->id, now()->toDateString(), '18:00:00', '20:00:00', 'approved');
        $this->createOvertime($inactiveUser->id, now()->toDateString(), '18:00:00', '20:00:00', 'pending');

        $this->service->generate($period->fresh());

        $records = PayrollRecord::query()
            ->where('payroll_period_id', $period->id)
            ->orderBy('user_id')
            ->get();

        $this->assertCount(2, $records);
        $this->assertSame(
            [$attendanceUser->id, $overtimeUser->id],
            $records->pluck('user_id')->all(),
        );

        $this->assertDatabaseMissing('payroll_records', [
            'payroll_period_id' => $period->id,
            'user_id'           => $inactiveUser->id,
        ]);
    }

    public function test_generate_keeps_period_open_when_no_payroll_data_exists(): void
    {
        $period = PayrollPeriod::query()->create([
            'name'       => 'March 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'open',
        ]);

        User::factory()->count(2)->create();

        $this->service->generate($period->fresh());

        $this->assertDatabaseCount('payroll_records', 0);
        $this->assertSame('open', $period->fresh()->status);
    }

    public function test_generate_never_stores_negative_net_salary(): void
    {
        $period = PayrollPeriod::query()->create([
            'name'       => 'March 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'open',
        ]);

        $this->settings->daily_wage = 1000;
        $this->settings->overtime_hourly_rate = 0;
        $this->settings->late_penalty_tier_1_min = 6;
        $this->settings->late_penalty_tier_1_amount = 200;
        $this->settings->late_penalty_tier_2_min = 16;
        $this->settings->late_penalty_tier_2_amount = 400;
        $this->settings->late_penalty_tier_3_percent = 200;

        $user = User::factory()->create();
        $this->createAttendance($user->id, '08:00:00', '08:45:00', '17:00:00');

        $this->service->generate($period->fresh());

        $record = PayrollRecord::query()
            ->where('user_id', $user->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('2000.00', $record->total_penalties);
        $this->assertSame('0.00', $record->net_salary);
    }

    private function createAttendance(int $userId, string $scheduleStart, string $actualStart, string $actualEnd): Attendance
    {
        return Attendance::query()->create([
            'user_id'             => $userId,
            'schedule_latitude'   => -6.200000,
            'schedule_longitude'  => 106.816666,
            'schedule_start_time' => $scheduleStart,
            'schedule_end_time'   => '17:00:00',
            'start_latitude'      => -6.200100,
            'start_longitude'     => 106.816700,
            'end_latitude'        => -6.200100,
            'end_longitude'       => 106.816700,
            'start_time'          => $actualStart,
            'end_time'            => $actualEnd,
            'is_leave'            => false,
            'start_photo_path'    => 'start.jpg',
            'end_photo_path'      => 'end.jpg',
        ]);
    }

    private function createOvertime(
        int $userId,
        string $date,
        string $startTime,
        string $endTime,
        string $status = 'approved',
    ): Overtime {
        return Overtime::query()->create([
            'user_id'    => $userId,
            'date'       => $date,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'status'     => $status,
            'reason'     => 'Monthly closing',
        ]);
    }
}
